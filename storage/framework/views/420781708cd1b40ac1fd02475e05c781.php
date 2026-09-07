<?php $__env->startSection('title', 'Challan: ' . $challan->challan_number); ?>

<?php $__env->startPush('styles'); ?>
    <style>
        /* 🖨️ PRO-GRADE A4 PRINT OPTIMIZATION */
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background-color: white !important;
            }

            body * {
                visibility: hidden;
            }

            #print-area,
            #print-area * {
                visibility: visible;
            }

            #print-area {
                filter: grayscale(100%) !important;
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 0;
                border: none !important;
                box-shadow: none !important;
            }

            .no-print {
                display: none !important;
            }

            .page-break-avoid {
                page-break-inside: avoid;
            }
        }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('header-title'); ?>
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Challan Details</h1>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        // Resolve Company and Store
        $company = $challan->company ?? auth()->user()->company;
        $store = $challan->store;

        // Signature belongs to the store that issued this challan, same as
        // quotations. signature_url returns null when unset, so the block
        // below simply renders the line without an image.
        $billingSignatureUrl = $store?->signature_url;

        // Party Details
        $partyName = $challan->party_name ?? 'Unknown Party';
        $partyPhone = $challan->party_phone ?? 'N/A';
        $partyAddress = $challan->party_address ?? 'N/A';
        $partyGSTIN = $challan->party_gst ?? null;
        $partyState = $challan->toState->name ?? ($challan->party_state ?? 'N/A');

        // Badge Color Mapping (Used for screen only)
        $colorMap = [
            'gray' => 'text-gray-700',
            'blue' => 'text-blue-700',
            'indigo' => 'text-indigo-700',
            'cyan' => 'text-cyan-700',
            'amber' => 'text-amber-600',
            'teal' => 'text-teal-700',
            'green' => 'text-green-600',
            'lime' => 'text-lime-700',
            'slate' => 'text-slate-700',
            'red' => 'text-red-600',
        ];
        $statusColorClass = $colorMap[$challan->status_color] ?? $colorMap['gray'];
    ?>

    <div class="pb-10">

        
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 no-print">
            <div class="w-full sm:w-auto">
                <?php if (isset($component)) { $__componentOriginal2e4e6bd15810bdc70e43e785f65cb0dc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2e4e6bd15810bdc70e43e785f65cb0dc = $attributes; } ?>
<?php $component = App\View\Components\Admin\Breadcrumb::resolve(['items' => [
                    ['label' => 'Challans', 'url' => route('admin.challans.index')],
                    ['label' => 'Challan Details'],
                ]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.breadcrumb'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Admin\Breadcrumb::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2e4e6bd15810bdc70e43e785f65cb0dc)): ?>
<?php $attributes = $__attributesOriginal2e4e6bd15810bdc70e43e785f65cb0dc; ?>
<?php unset($__attributesOriginal2e4e6bd15810bdc70e43e785f65cb0dc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2e4e6bd15810bdc70e43e785f65cb0dc)): ?>
<?php $component = $__componentOriginal2e4e6bd15810bdc70e43e785f65cb0dc; ?>
<?php unset($__componentOriginal2e4e6bd15810bdc70e43e785f65cb0dc); ?>
<?php endif; ?>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <?php if(has_permission('challans.update')): ?>
                    <?php if($challan->status !== 'cancelled'): ?>
                        <a href="<?php echo e(route('admin.challans.edit', $challan->id)); ?>"
                            class="bg-white border border-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-50 transition-colors flex items-center gap-2 shadow-sm">
                            <i data-lucide="pencil" class="w-4 h-4"></i> Edit
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <button onclick="window.print()"
                    class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2">
                    <i data-lucide="printer" class="w-4 h-4"></i> Print
                </button>

                <?php if(has_permission('challans.download_pdf')): ?>
                    <a href="<?php echo e(route('admin.challans.pdf', $challan->id)); ?>" target="_blank"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2 shadow-sm">
                        <i data-lucide="download" class="w-4 h-4"></i> PDF
                    </a>
                <?php endif; ?>

                <?php
                    $linkedInvoices = $challan->invoices;
                    $canConvert =
                        $challan->direction === 'outward' &&
                        !in_array($challan->status, ['draft', 'cancelled', 'converted_to_invoice', 'fully_returned']) &&
                        $challan->items->sum('qty_pending') > 0;
                ?>

                <?php $__currentLoopData = $linkedInvoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $linkedInvoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route('admin.invoices.show', $linkedInvoice->id)); ?>"
                        class="bg-emerald-50 border border-emerald-200 text-emerald-700 hover:bg-emerald-100 px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                        <i data-lucide="file-check" class="w-4 h-4"></i>
                        <?php echo e($linkedInvoice->invoice_number); ?>

                        <span class="text-[11px] uppercase opacity-70"><?php echo e($linkedInvoice->status); ?></span>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php if($canConvert): ?>
                    <a href="<?php echo e(route('admin.invoices.create', ['challan_id' => $challan->id])); ?>"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2 shadow-sm">
                        <i data-lucide="file-plus" class="w-4 h-4"></i> Invoice
                    </a>
                <?php endif; ?>

                <?php
                    $waText = urlencode(
                        "Hello {$partyName},\nHere is your {$challan->type_label} Document #{$challan->challan_number} dated " .
                            $challan->challan_date->format('d M Y') .
                            ".\nThank you for your business!",
                    );
                    $waPhone = preg_replace('/[^0-9]/', '', $partyPhone);
                ?>
                <?php if(strlen($waPhone) >= 10): ?>
                    <a href="https://wa.me/<?php echo e($waPhone); ?>?text=<?php echo e($waText); ?>" target="_blank"
                        class="bg-[#25D366] hover:bg-[#1da851] text-white px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.669.149-.198.297-.768.966-.941 1.164-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.058-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.148-.173.198-.297.297-.495.099-.198.05-.371-.025-.52-.074-.149-.669-1.612-.916-2.206-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.371-.273.297-1.04 1.016-1.04 2.479 0 1.463 1.064 2.876 1.213 3.074.148.198 2.095 3.2 5.076 4.487.709.306 1.262.489 1.693.626.711.226 1.358.194 1.87.118.571-.085 1.758-.718 2.007-1.411.248-.694.248-1.289.173-1.411-.074-.124-.272-.198-.57-.347z" />
                            <path
                                d="M12.004 2C6.486 2 2 6.484 2 12c0 1.991.585 3.847 1.589 5.407L2 22l4.75-1.557A9.956 9.956 0 0012.004 22C17.522 22 22 17.516 22 12S17.522 2 12.004 2z" />
                        </svg>
                        WhatsApp
                    </a>
                <?php endif; ?>
            </div>
        </div>

        
        <?php if($challan->is_returnable && $challan->is_return_overdue): ?>
            <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 no-print flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="bg-red-600 p-2 rounded-lg text-white">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-red-800">Return Overdue</h4>
                        <p class="text-xs text-red-600 font-medium">This challan was due for return on
                            <?php echo e($challan->return_due_date->format('d M, Y')); ?>.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        
        <div id="print-area"
            class="w-full bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden text-gray-800 print:shadow-none print:border-none">
            <div class="p-8 md:p-12">

                
                <div
                    class="p-4 md:p-8 pb-4 flex flex-col md:flex-row print:flex-row justify-between items-start gap-6 md:gap-0">
                    <div>
                        <div
                            class="inline-block bg-gray-800 text-white px-3 py-1 text-[10px] font-black uppercase tracking-widest mb-3">
                            <?php echo e($challan->type_label); ?>

                        </div>
                        <h1 class="text-3xl font-black uppercase tracking-tighter text-gray-900 leading-none">
                            # <?php echo e($challan->challan_number); ?>

                        </h1>
                        <div class="text-[12px] text-gray-500 font-bold mt-2">
                            Date: <?php echo e($challan->challan_date->format('d M Y')); ?>

                        </div>
                    </div>

                    <div
                        class="text-left md:text-right print:text-right flex flex-col items-start md:items-end print:items-end">
                        <h2 class="text-xl font-black text-gray-900 uppercase leading-none"><?php echo e($company->name); ?></h2>
                        <div class="text-gray-600 text-[12px] mt-1 font-medium">
                            <?php if($company->gst_number): ?>
                                GSTIN: <span
                                    class="font-bold text-gray-900 uppercase"><?php echo e($company->gst_number); ?></span><br>
                            <?php endif; ?>
                            Email: <?php echo e($company->email); ?><br>
                            Phone: <?php echo e($company->phone); ?>

                        </div>

                        <?php if($store): ?>
                            <div class="mt-4 text-right">
                                <h3
                                    class="text-[10px] font-black text-gray-700 uppercase tracking-widest leading-none mb-1">
                                    Dispatched From: <span class="text-black"><?php echo e($store->name); ?></span>
                                </h3>
                                <div class="text-gray-800 text-[12px] leading-tight font-medium">
                                    <?php echo e($store->address); ?><br>
                                    <?php echo e($store->city); ?>, <?php echo e($store->state->name ?? ''); ?>

                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                
                <div class="mx-8 border-t-2 border-gray-900"></div>

                
                
                <div class="p-4 md:p-8 grid grid-cols-1 md:grid-cols-2 print:grid-cols-2 gap-8 md:gap-12">
                    
                    <div>
                        <h3 class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2">
                            <?php echo e($challan->direction === 'outward' ? 'Dispatched To / Billed To' : 'Received From'); ?>

                        </h3>
                        <div class="text-sm text-gray-800">
                            <div class="font-black text-base mb-0.5 uppercase"><?php echo e($partyName); ?></div>
                            <?php if($partyGSTIN): ?>
                                <div class="font-bold text-gray-900 uppercase">GSTIN: <?php echo e($partyGSTIN); ?></div>
                            <?php endif; ?>
                            <?php if($partyAddress !== 'N/A'): ?>
                                <div class="text-gray-600 leading-snug font-medium mt-1">
                                    <?php echo nl2br(e($partyAddress)); ?>

                                </div>
                            <?php endif; ?>
                            <?php if($partyState !== 'N/A'): ?>
                                <div class="text-gray-600 leading-snug font-medium mt-1">State: <?php echo e($partyState); ?></div>
                            <?php endif; ?>
                            <?php if($partyPhone !== 'N/A'): ?>
                                <div class="text-gray-600 leading-snug font-medium">Phone: <?php echo e($partyPhone); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    
                    <div class="space-y-1">
                        <div class="grid grid-cols-2 text-[13px]">
                            <span class="font-bold text-gray-500">Direction:</span>
                            <span class="text-right font-bold text-gray-900 uppercase"><?php echo e($challan->direction); ?></span>
                        </div>
                        <div class="grid grid-cols-2 text-[13px]">
                            <span class="font-bold text-gray-500">Status:</span>
                            <span
                                class="text-right font-black uppercase <?php echo e($statusColorClass); ?>"><?php echo e($challan->status_label); ?></span>
                        </div>

                        <?php if($challan->transport_name): ?>
                            <div class="grid grid-cols-2 text-[13px]">
                                <span class="font-bold text-gray-500">Transporter:</span>
                                <span
                                    class="text-right font-bold text-gray-900 uppercase"><?php echo e($challan->transport_name); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if($challan->vehicle_number): ?>
                            <div class="grid grid-cols-2 text-[13px]">
                                <span class="font-bold text-gray-500">Vehicle No:</span>
                                <span
                                    class="text-right font-bold text-gray-900 uppercase"><?php echo e($challan->vehicle_number); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if($challan->eway_bill_number): ?>
                            <div class="grid grid-cols-2 text-[13px]">
                                <span class="font-bold text-gray-500">E-Way Bill:</span>
                                <span class="text-right font-bold text-gray-900"><?php echo e($challan->eway_bill_number); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if($challan->is_returnable && $challan->return_due_date): ?>
                            <div class="grid grid-cols-2 text-[13px] pt-1 border-t border-gray-100">
                                <span class="font-bold text-gray-500">Return Due:</span>
                                <span
                                    class="text-right font-black <?php echo e($challan->is_return_overdue ? 'text-red-600' : 'text-gray-900'); ?>">
                                    <?php echo e($challan->return_due_date->format('d M Y')); ?>

                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                
                
                <div class="overflow-x-auto print:overflow-visible mb-10 px-4 md:px-8">
                    <table class="w-full text-sm print:text-[11px] border-collapse">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="py-3 px-4 text-left font-bold border-b">Description</th>
                                <th class="py-3 px-4 text-center font-bold border-b">HSN/SAC</th>
                                <?php if(batch_enabled()): ?>
                                    <th class="py-3 px-4 text-center font-bold border-b">Batch #</th>
                                    <th class="py-3 px-4 text-center font-bold border-b">Expiry</th>
                                <?php endif; ?>
                                <th class="py-3 px-4 text-right font-bold border-b">Qty Sent</th>
                                <th class="py-3 px-4 text-right font-bold border-b text-orange-600">Returned</th>
                                <th class="py-3 px-4 text-right font-bold border-b text-green-600">Invoiced</th>
                                <th class="py-3 px-4 text-right font-bold border-b text-blue-600">Pending</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $challan->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50/50">
                                    <td class="py-4 px-4 text-gray-800">
                                        <div class="font-bold"><?php echo e($item->product_name); ?></div>
                                        <div class="text-[11px] text-gray-500 font-mono mt-0.5">SKU:
                                            <?php echo e($item->sku_code ?? '-'); ?></div>
                                    </td>
                                    <td class="py-4 px-4 text-center text-gray-500"><?php echo e($item->hsn_code ?? '-'); ?></td>

                                    <?php if(batch_enabled()): ?>
                                        <td class="py-4 px-4 text-center font-mono text-[12px] text-gray-700">
                                            <?php echo e($item->batch_number ?? '-'); ?></td>
                                        <td class="py-4 px-4 text-center text-[12px]">
                                            <?php if($item->expiry_date): ?>
                                                <span
                                                    class="<?php echo e($item->expiry_date->isPast() ? 'text-red-600 font-bold' : 'text-gray-600'); ?>">
                                                    <?php echo e($item->expiry_date->format('d M Y')); ?>

                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>

                                    <td class="py-4 px-4 text-right font-bold text-gray-900"><?php echo e((float) $item->qty_sent); ?>

                                    </td>
                                    <td
                                        class="py-4 px-4 text-right font-semibold <?php echo e($item->qty_returned > 0 ? 'text-orange-600' : 'text-gray-400'); ?>">
                                        <?php echo e((float) $item->qty_returned); ?>

                                    </td>
                                    <td
                                        class="py-4 px-4 text-right font-semibold <?php echo e($item->qty_invoiced > 0 ? 'text-green-600' : 'text-gray-400'); ?>">
                                        <?php echo e((float) $item->qty_invoiced); ?>

                                    </td>
                                    <td
                                        class="py-4 px-4 text-right font-bold <?php echo e($item->qty_pending > 0 ? 'text-blue-600' : 'text-gray-400'); ?>">
                                        <?php echo e((float) $item->qty_pending); ?>

                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>

                
                <div
                    class="px-4 md:px-8 pb-12 flex flex-col md:flex-row print:flex-row justify-between items-start gap-8 md:gap-10 page-break-avoid border-t border-gray-200 pt-6">

                    
                    <div class="w-full md:flex-1 space-y-4">
                        <?php if($challan->purpose_note): ?>
                            <div class="text-[12px] text-gray-600">
                                <strong class="text-gray-800 uppercase tracking-widest text-[10px]">Purpose of
                                    Challan:</strong>
                                <p class="mt-1 leading-relaxed"><?php echo e($challan->purpose_note); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if($challan->internal_notes): ?>
                            <div class="no-print bg-yellow-50 p-3 rounded-lg border border-yellow-200 mt-2">
                                <h4 class="text-[10px] font-black text-yellow-700 uppercase tracking-widest mb-1">Internal
                                    Notes (Hidden on Print)</h4>
                                <p class="text-[12px] text-yellow-800"><?php echo e($challan->internal_notes); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    
                    <div class="w-full md:w-[300px] print:w-[300px] ml-auto print:ml-auto flex flex-col items-end">
                        <table class="w-full text-[13px] border-collapse">
                            
                            <tr class="border-t-2 border-gray-900">
                                <td class="py-3 text-[14px] font-black text-gray-900 uppercase">Total Quantity</td>
                                <td class="py-3 text-right text-[16px] font-black text-gray-900 whitespace-nowrap">
                                    <?php echo e((float) $challan->total_qty); ?>

                                </td>
                            </tr>
                        </table>

                        
                        <div class="mt-20 w-full flex items-start justify-between gap-8">
                            <div class="flex-1 min-w-[120px] text-center flex flex-col">
                                <div class="h-12 mb-1"></div>
                                <div
                                    class="border-t border-gray-400 pt-1 text-[10px] font-bold text-gray-800 uppercase tracking-wider">
                                    Receiver Sign
                                </div>
                            </div>
                            <div class="flex-1 min-w-[120px] text-center flex flex-col">
                                <div class="h-12 mb-1 flex items-end justify-center">
                                    <?php if($billingSignatureUrl): ?>
                                        <img src="<?php echo e($billingSignatureUrl); ?>" alt="Authorized Signature"
                                            class="max-h-12 object-contain opacity-90" />
                                    <?php endif; ?>
                                </div>
                                <div
                                    class="border-t border-gray-400 pt-1 text-[10px] font-bold text-gray-800 uppercase tracking-wider">
                                    Authorized Sign
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\qlinkongraphics\Desktop\MyLab\plantiq-local\resources\views/admin/challans/show.blade.php ENDPATH**/ ?>