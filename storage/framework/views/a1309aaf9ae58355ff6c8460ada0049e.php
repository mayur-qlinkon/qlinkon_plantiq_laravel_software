<?php $__env->startSection('title', 'Return: ' . $challanReturn->return_number); ?>

<?php $__env->startPush('styles'); ?>
    <style>
        /* 🖨️ A4 PRINT OPTIMIZATION */
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
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Return Details</h1>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        // Resolve Company and Store
        $company = $challanReturn->company ?? auth()->user()->company;
        $challan = $challanReturn->challan;
        $store = $challan->store;

        // Party Details (From original Challan)
        $partyName = $challan->party_name ?? 'Unknown Party';
        $partyPhone = $challan->party_phone ?? 'N/A';
        $partyAddress = $challan->party_address ?? 'N/A';
        $partyGSTIN = $challan->party_gst ?? null;
        $partyState = $challan->toState->name ?? $challan->party_state ?? 'N/A';

        // Badge Color Mapping for Condition
        $colorMap = [
            'green' => 'bg-green-50 text-green-700 border-green-200',
            'red'   => 'bg-red-50 text-red-700 border-red-200',
            'amber' => 'bg-amber-50 text-amber-700 border-amber-200',
            'gray'  => 'bg-gray-50 text-gray-700 border-gray-200',
        ];
        $conditionColorClass = $colorMap[$challanReturn->condition_color] ?? $colorMap['gray'];
    ?>

    <div class="pb-10">
        
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 no-print">
            <div class="w-full sm:w-auto">
                <?php if (isset($component)) { $__componentOriginal2e4e6bd15810bdc70e43e785f65cb0dc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2e4e6bd15810bdc70e43e785f65cb0dc = $attributes; } ?>
<?php $component = App\View\Components\Admin\Breadcrumb::resolve(['items' => [
                    ['label' => 'Challan Return', 'url' => route('admin.challan-returns.index')],
                    ['label' => 'Challan Return Details'],
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
                <a href="<?php echo e(route('admin.challan-returns.index')); ?>"
                    class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-600 px-4 py-1.5 rounded text-sm transition-colors flex items-center shadow-sm font-medium">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1.5"></i> Back
                </a>

                <?php if(has_permission('challan_returns.update')): ?>
                    <a href="<?php echo e(route('admin.challan-returns.edit', $challanReturn->id)); ?>"
                        class="bg-white border border-gray-200 hover:bg-blue-50 hover:text-blue-600 text-gray-600 px-4 py-1.5 rounded text-sm transition-colors flex items-center shadow-sm font-medium">
                        <i data-lucide="pencil" class="w-4 h-4 mr-1.5"></i> Edit Notes
                    </a>
                <?php endif; ?>

                <button onclick="window.print()"
                    class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-800 px-4 py-1.5 rounded text-sm transition-colors flex items-center gap-1.5 shadow-sm font-bold">
                    <i data-lucide="printer" class="w-4 h-4"></i> Print Receipt
                </button>

                <?php if(has_permission('challan_returns.download_pdf')): ?>
                    <a href="<?php echo e(route('admin.challan-returns.pdf', $challanReturn->id)); ?>" target="_blank"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded text-sm transition-colors flex items-center gap-1.5 shadow-sm font-bold">
                        <i data-lucide="download" class="w-4 h-4"></i> Download PDF
                    </a>
                <?php endif; ?>
            </div>
        </div>

        
        <div id="print-area" class="w-full bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden text-gray-800 print:grayscale print:shadow-none print:border-none">

            
            <div class="p-8 border-b-2 border-gray-800 grid grid-cols-2 gap-6 items-start">
                <div>
                    <h1 class="text-3xl font-black uppercase tracking-widest text-gray-900 mb-1">Goods Return</h1>
                    <div class="text-sm text-gray-600 font-bold mb-3"># <?php echo e($challanReturn->return_number); ?></div>

                    
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-block border text-[11px] font-black uppercase px-2.5 py-1 rounded <?php echo e($conditionColorClass); ?>">
                            <?php echo e($challanReturn->condition_label); ?>

                        </span>
                        
                        <span class="inline-block border border-gray-200 bg-gray-50 text-gray-700 text-[11px] font-black uppercase px-2.5 py-1 rounded">
                            <i data-lucide="undo-2" class="w-3 h-3 inline pb-0.5"></i> Inward Return
                        </span>
                    </div>
                </div>

                <div class="text-right text-sm flex flex-col items-end">
                    
                    <h2 class="text-xl font-black text-gray-900 uppercase leading-none"><?php echo e($company->name); ?></h2>
                    <div class="text-gray-600 text-[12px] mt-1">
                        <?php if($company->gst_number || $company->gstin): ?>
                            GSTIN: <span class="font-bold text-gray-900 uppercase"><?php echo e($company->gst_number ?? $company->gstin); ?></span><br>
                        <?php endif; ?>
                        Email: <?php echo e($company->email); ?><br>
                        Phone: <?php echo e($company->phone); ?>

                    </div>

                    
                    <?php if($store): ?>
                        <div class="text-right mt-4">
                            <h3 class="text-[10px] font-black text-gray-700 uppercase tracking-widest">Returned To Branch: <span class="font-bold text-black"><?php echo e($store->name); ?></span></h3>
                            <div class="text-gray-800 text-[13px] leading-tight">
                                <?php if($store->address): ?>
                                    <?php echo e($store->address); ?><br>
                                <?php endif; ?>
                                <?php echo e($store->city); ?><?php echo e($store->city && $store->zip_code ? ', ' : ''); ?><?php echo e($store->zip_code); ?><br>
                                <?php echo e($store->state->name ?? $store->state_id); ?>

                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="p-8 grid grid-cols-2 gap-8 border-b border-gray-200">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Returned From (Party)</h3>
                        <div class="text-sm text-gray-800">
                            <div class="font-bold text-base mb-0.5"><?php echo e($partyName); ?></div>
                            
                            <?php if($partyGSTIN): ?>
                                <div class="font-bold text-gray-900 uppercase">GSTIN: <?php echo e($partyGSTIN); ?></div>
                            <?php endif; ?>
                            <?php if($partyAddress !== 'N/A'): ?>
                                <div class="text-gray-600 leading-tight mt-1"><?php echo e($partyAddress); ?></div>
                            <?php endif; ?>
                            <?php if($partyPhone !== 'N/A'): ?>
                                <div class="text-gray-600 mt-1">Phone: <?php echo e($partyPhone); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="space-y-1 bg-gray-50 p-4 rounded-xl border border-gray-100">
                    <div class="grid grid-cols-2 text-[13px]">
                        <span class="font-bold text-gray-500">Return Date:</span>
                        <span class="text-right font-semibold"><?php echo e($challanReturn->return_date->format('d M Y')); ?></span>
                    </div>
                    
                    <div class="grid grid-cols-2 text-[13px]">
                        <span class="font-bold text-gray-500">Original Challan:</span>
                        <span class="text-right font-bold text-blue-600">
                            <a href="<?php echo e(route('admin.challans.show', $challan->id)); ?>" class="hover:underline no-print"><?php echo e($challan->challan_number); ?></a>
                            <span class="hidden print:inline"><?php echo e($challan->challan_number); ?></span>
                        </span>
                    </div>

                    <div class="grid grid-cols-2 text-[13px]">
                        <span class="font-bold text-gray-500">Challan Type:</span>
                        <span class="text-right font-semibold text-gray-900"><?php echo e($challan->type_label); ?></span>
                    </div>
                    
                    <?php if($challanReturn->vehicle_number): ?>
                        <div class="grid grid-cols-2 text-[13px] pt-2 mt-2 border-t border-gray-200">
                            <span class="font-bold text-gray-500">Return Vehicle No:</span>
                            <span class="text-right font-bold text-gray-900 uppercase"><?php echo e($challanReturn->vehicle_number); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($challanReturn->received_by): ?>
                        <div class="grid grid-cols-2 text-[13px]">
                            <span class="font-bold text-gray-500">Received By:</span>
                            <span class="text-right font-semibold text-gray-900"><?php echo e($challanReturn->received_by); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="px-8 py-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead>
                            <tr class="border-b-2 border-gray-800 text-xs font-black text-gray-700 uppercase tracking-wider">
                                <th class="py-3 px-2">Description</th>
                                <th class="py-3 px-2 text-center">Original Qty</th>
                                <th class="py-3 px-2 text-center bg-gray-50">Returned Qty</th>
                                <th class="py-3 px-2 text-center text-red-600">Damaged Qty</th>
                                <th class="py-3 px-2 text-left">Damage Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php $__currentLoopData = $challanReturn->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td class="py-4 px-2">
                                        <div class="font-bold text-gray-900"><?php echo e($item->challanItem->product_name ?? 'Unknown Product'); ?></div>
                                        <div class="text-[11px] text-gray-500 font-mono mt-0.5">SKU: <?php echo e($item->challanItem->sku_code ?? 'N/A'); ?></div>
                                    </td>
                                    
                                    
                                    <td class="py-4 px-2 text-center text-gray-500"><?php echo e((float) ($item->challanItem->qty_sent ?? 0)); ?></td>
                                    
                                    
                                    <td class="py-4 px-2 text-center font-black text-gray-900 bg-gray-50/50">
                                        <?php echo e((float) $item->qty_returned); ?>

                                    </td>

                                    
                                    <td class="py-4 px-2 text-center font-bold <?php echo e($item->qty_damaged > 0 ? 'text-red-600' : 'text-gray-400'); ?>">
                                        <?php echo e((float) $item->qty_damaged); ?>

                                    </td>

                                    
                                    <td class="py-4 px-2 text-left text-gray-600 max-w-[250px] whitespace-normal text-xs">
                                        <?php echo e($item->damage_note ?: '-'); ?>

                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <div class="px-8 pb-8 page-break-avoid flex flex-col md:flex-row print:flex-row justify-between items-end gap-8 print:gap-4 border-t border-gray-100 pt-6">

                
                <div class="w-full md:w-1/2 print:w-1/2 mb-6 md:mb-0 print:mb-0 space-y-6">
                    <?php if($challanReturn->notes): ?>
                        <div>
                            <h4 class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-1">Return Notes</h4>
                            <p class="text-[13px] text-gray-700 font-medium"><?php echo e($challanReturn->notes); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                
                <div class="w-full md:w-[350px] flex flex-col items-end">
                    <div class="w-full bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <table class="w-full text-[13px]">
                            <tbody>
                                <tr>
                                    <td class="py-1.5 text-gray-600 font-semibold">Total Quantity Returned</td>
                                    <td class="py-1.5 text-right text-gray-900 font-bold"><?php echo e((float) $challanReturn->total_qty_returned); ?></td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-red-600 font-semibold">Total Damaged</td>
                                    <td class="py-1.5 text-right text-red-600 font-bold"><?php echo e((float) $challanReturn->total_qty_damaged); ?></td>
                                </tr>
                                
                                <tr class="border-t border-gray-200">
                                    <td class="pt-3 pb-1 text-[12px] font-black text-green-700 uppercase tracking-wider">Clean Stock Recovered</td>
                                    <td class="pt-3 pb-1 text-right text-[18px] font-black text-green-700">
                                        <?php echo e((float) $challanReturn->total_qty_clean); ?>

                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            
            <div class="px-8 pb-10 pt-16 w-full page-break-avoid flex justify-between items-end mt-auto">
                <div class="text-left">
                    <div class="border-t-2 border-gray-400 pt-2 inline-block min-w-[200px] text-[11px] font-bold text-gray-800 uppercase tracking-wider text-center">
                        Party Signature
                    </div>
                </div>
                <div class="text-right">
                    <div class="border-t-2 border-gray-400 pt-2 inline-block min-w-[200px] text-[11px] font-bold text-gray-800 uppercase tracking-wider text-center">
                        Receiver (Store Auth)
                    </div>
                </div>
            </div>

        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\qlinkongraphics\Desktop\MyLab\plantiq-local\resources\views/admin/challan-returns/show.blade.php ENDPATH**/ ?>