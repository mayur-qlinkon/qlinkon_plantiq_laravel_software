# PHASE 1 REPORT — Global unique index sweep

No application code was modified. Test harness lives in the scratchpad, outside the app tree.

## Browser isolation — verified first

Two independent Playwright browser contexts, separate cookie jars, persisted storage state. Proof:

```
after both logins:
  A -> {"url":".../admin/dashboard","alpha":3,"beta":0}
  B -> {"url":".../admin/dashboard","alpha":0,"beta":3}
reload A after B login:
  A -> {"url":".../admin/dashboard","alpha":3,"beta":0}   <- still Alpha
  B -> {"url":".../admin/dashboard","alpha":0,"beta":3}
```

Both sessions stay logged in across scripts.

---

## Table 1 — Schema sweep

Every UNIQUE index on a table that **has** a `company_id` column, where the index **does not** include `company_id`. 13 hits, all classified.

| table | index name | columns | has company_id col? | value origin | verdict |
|---|---|---|---|---|---|
| **challan_returns** | `challan_returns_return_number_unique` | `return_number` | yes | **PER-TENANT GENERATED** — [ChallanReturn.php:205](app/Models/ChallanReturn.php:205) scopes `where('company_id',…)` | **BLOCKER — verified** |
| **suppliers** | `suppliers_gstin_unique` | `gstin` | yes | **TENANT-SUPPLIED REAL-WORLD VALUE** — free-text field, zero uniqueness validation ([StoreSupplierRequest.php:44](app/Http/Requests/Admin/StoreSupplierRequest.php:44)) | **BLOCKER — verified** |
| appointments | `appointments_active_booking_unique` | `active_booking_key` | yes | generated as `company_id-date-slot_id` ([Appointment.php:156](app/Models/Appointment/Appointment.php:156)) — company_id is embedded in the value | SAFE |
| attribute_values | `attribute_values_attribute_id_value_unique` | `attribute_id,value` | yes | transitively scoped: `attribute_id` → `attributes.company_id` | SAFE |
| employee_salary_structures | `emp_sal_struct_unique` | `employee_id,salary_component_id,effective_from` | yes | transitively scoped: `employee_id` → `employees.company_id` | SAFE |
| plans | `plans_slug_unique` | `slug` | yes (always NULL) | platform-level only — created solely by super-admin onboarding with `company_id => null` and a random suffix ([CompanyOnboardingService.php:271](app/Services/Platform/CompanyOnboardingService.php:271)) | SAFE / out of scope |
| product_stocks | `product_stocks_product_sku_id_warehouse_id_unique` | `product_sku_id,warehouse_id` | yes | transitively scoped: both FKs point at tenant-owned tables | SAFE |
| production_daily_tasks | `pdt_unique_task` | `plant_batch_id,activity_template_id,due_date` | yes | transitively scoped: `plant_batch_id` → `production_plant_batches.company_id` | SAFE |
| production_growing_spaces | `production_growing_spaces_…_unique` | `production_site_id,zone_id,name` | yes | transitively scoped: `production_site_id` → `production_sites.company_id` | SAFE |
| stores | `stores_domain_unique` | `domain` | yes | globally unique **by nature** — DNS host routing | SAFE by design |
| stores | `stores_subdomain_unique` | `subdomain` | yes | globally unique **by nature** — DNS host routing | SAFE by design |
| subscription_payment_logs | `subscription_payment_logs_cf_order_id_unique` | `cf_order_id` | yes | generated as `sub_{companyId}_{timestamp}` ([SubscriptionRenewalController.php:221](app/Http/Controllers/Admin/SubscriptionRenewalController.php:221)) — company_id embedded; the index is a deliberate idempotency guard | SAFE cross-tenant |
| work_logs | `work_logs_unique` | `employee_id,log_date,start_time` | yes | transitively scoped: `employee_id` → `employees.company_id` | SAFE |

All seven "transitively scoped" claims confirmed against `information_schema.KEY_COLUMN_USAGE` — every referenced parent table carries `company_id`. Not asserted from naming.

**Both of your suspicions were correct. Neither was a false alarm.**

### Contrast — the document-number family is otherwise clean

18 unique indexes *do* include `company_id` alongside a generated number/code: invoices, purchases, payments, quotations, orders, purchase_returns, invoice_returns, invoice_write_offs, challans, expenses, salary_slips, appointments, employees, promotions, leave_types, salary_components, production_plant_batches, product_skus. **`challan_returns` is the single remaining outlier** — the one table the `fix_expense_number_unique_scope` pattern was never applied to.

---

## Table 2 — Browser reproductions

| # | suspect | steps | what the second tenant saw | SQLSTATE | partial write? | severity |
|---|---|---|---|---|---|---|
| **1** | `suppliers.gstin` | 1. Browser A → `/admin/suppliers` → **Add Supplier** → name `Shared Pot Works`, GSTIN `24ZZZZZ9999Z1Z9` → **Save Supplier**. Saved, back on list.<br>2. Browser B → same form → same GSTIN, its own name/phone → **Save Supplier**. | **Unhandled 500 error page.** `BizAlert.loading('Saving...')` fires, then a full-page `Illuminate\Database\UniqueConstraintViolationException` at `Connection.php:838`. Body renders the **entire INSERT statement**, DB host, port and schema name. | `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '24ZZZZZ9999Z1Z9' for key 'suppliers.suppliers_gstin_unique'` | **No.** Single INSERT, nothing else touched. Only Alpha's row `id=5, company_id=1` exists. | **BLOCKER** |
| **2** | `challan_returns.return_number` | 1. Browser B → `/admin/challan-returns/create?challan=2` → tick line item → **Confirm Return**. Saved → redirected to `Return: CR-2026-0001`.<br>2. Browser A → `/admin/challan-returns/create?challan=1` → tick line item → **Confirm Return**. | **Nothing.** Page reloads to the same create form. No error banner, no red toast, no SweetAlert, no validation list. Verified programmatically: `"Failed to process return"` → **false**, `"SQLSTATE"` → **false**, `swal2/[role=alert]` nodes → **0**. The tenant clicks the button and the screen just resets. | `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'CR-2026-0001' for key 'challan_returns.challan_returns_return_number_unique'` (logged only, in `storage/logs/laravel.log`) | **No.** Failing insert is the first write inside `DB::transaction` ([ChallanReturnService.php:54](app/Services/ChallanReturnService.php:54)); clean rollback. `challan_items.qty_returned` stayed `0.00`, no orphan items, auto-increment id burned. | **BLOCKER** |

### Step 3 — reverse direction and same-tenant control

| test | result |
|---|---|
| `gstin`: **B first**, then A (`27YYYYY8888Y1Y8`) | B saved; **A got the 500.** Symmetric — whoever is second loses. |
| `gstin`: same tenant, *different* GSTIN | Saved fine. Sequence/uniqueness works when not colliding. |
| `gstin`: same tenant, *duplicate* GSTIN | Also an unhandled 500. Should be a validation message; instead it's a crash. |
| `gstin`: value held only by a **soft-deleted** row | Blocked for Beta **and for Alpha, the owner**. `deleted_at` does not free the value — the GSTIN is burned permanently, system-wide, by a row nobody can see. |
| `challan_returns`: **B first**, then A | B → `CR-2026-0001` saved. **A silently blocked.** Symmetric. |
| `challan_returns`: same tenant, second return | B → `CR-2026-0002`. Sequence increments correctly when it is not colliding. |
| `challan_returns`: blocked tenant retries on a different challan | Blocked again, identical error. **Permanent** — the generator always returns `CR-2026-0001` for a tenant with zero rows. |

Prerequisite challans were created in each tenant through the real POST route from inside the logged-in page. Both tenants got `DC-2026-0001` with no collision — `challans` has the correct composite index, which is exactly the contrast that isolates the defect.

---

## Findings

**F1 — BLOCKER — `suppliers.gstin` global unique index**
`database/migrations/0001_01_01_000005_create_suppliers_table.php:47` — `$table->string('gstin', 15)->nullable()->unique();`
Two tenants buying from the same manufacturer cannot both record that manufacturer. The second gets an unhandled 500. Soft-deleting the row does not release the value, for anyone, ever.
*Fix (one line):* add a composite unique on `(company_id, gstin, deleted_at)` and drop the global `suppliers_gstin_unique`; add a matching company-scoped `Rule::unique` to `StoreSupplierRequest`/`UpdateSupplierRequest` so it surfaces as a field error instead of a crash.

**F2 — BLOCKER — `challan_returns.return_number` global unique index**
`database/migrations/2026_03_26_153359_*.php:21` — `$table->string('return_number', 50)->unique();` against a per-company generator at `app/Models/ChallanReturn.php:205`.
The second tenant to ever raise a challan return is permanently locked out of the feature, with **no error message of any kind**.
*Fix (one line):* add a composite unique on `(company_id, return_number)` and drop the global `challan_returns_return_number_unique` — the same shape as `2026_08_21_100000_fix_expense_number_unique_scope.php`.

---

## UNVERIFIED (not reproduced in the browser — do not act on these yet)

1. **LEAK (inference oracle), F1/F2 side effect.** With `APP_DEBUG=false` the SQL is hidden, but the *outcome* still differs: success vs. failure. A tenant can probe whether any other tenant already holds a given GSTIN. Real but low-value, and I have not tested it with debug off.
2. **`ChallanReturnController::store` swallows the error into a flash that no page renders.** `back()->withInput()->with('error', …)` at [ChallanReturnController.php:103](app/Http/Controllers/Admin/ChallanReturnController.php:103) lands on `challan-returns/create.blade.php`, which only renders `$errors`, never `session('error')`; `layouts/admin.blade.php` has no global flash block. I confirmed the message is absent from the DOM, but I have not swept how many other create-screens share this silent-failure shape. That sweep is worth its own pass.
3. **`ChallanReturn::generateNumber` uses `lockForUpdate()` on a SELECT that matches zero rows** for a tenant's first document — no row to lock means no gap protection. Potential same-tenant RACE producing duplicates. Not tested; belongs in a concurrency phase.

Stopping here. Ready for Phase 2.