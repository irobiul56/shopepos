<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount } from 'vue';

const { props } = usePage();
const sale = ref(props.sale);
const shop = ref(props.shop);
const isPrinting = ref(false);
let printWindow = null;

// Auto print on mount
onMounted(() => {
    // Small delay to ensure DOM is fully rendered
    setTimeout(() => {
        printInvoice();
    }, 500);
});

// Clean up when component unmounts
onBeforeUnmount(() => {
    if (printWindow && !printWindow.closed) {
        printWindow.close();
    }
});

const printInvoice = () => {
    const printContent = document.getElementById('print-invoice-content');
    if (!printContent) {
        console.error('Print content not found');
        return;
    }
    
    // Check if already printing
    if (isPrinting.value) {
        return;
    }
    
    isPrinting.value = true;
    
    printWindow = window.open('', '_blank', 'width=800,height=600');
    if (!printWindow) {
        alert('Please allow popups for printing');
        isPrinting.value = false;
        // If popup blocked, go back to POS
        setTimeout(() => {
            goBackToPos();
        }, 500);
        return;
    }
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Invoice - ${sale.value?.invoice_no || 'Print'}</title>
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: white; }
                .invoice-container { max-width: 800px; margin: 0 auto; }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .header { margin-bottom: 30px; }
                .shop-name { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
                .invoice-title { font-size: 20px; font-weight: bold; margin: 20px 0; }
                .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f5f5f5; }
                .summary { width: 300px; margin-left: auto; }
                .summary-row { display: flex; justify-content: space-between; padding: 5px 0; }
                .total-row { font-weight: bold; font-size: 18px; border-top: 2px solid #000; margin-top: 10px; padding-top: 10px; }
                .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
                @media print { body { margin: 0; padding: 0; } }
                .no-print { display: none; }
            </style>
        </head>
        <body>
            <div class="invoice-container">
                ${printContent.innerHTML}
            </div>
            <script>
                // Track if print was completed
                let printCompleted = false;
                
                window.onload = function() {
                    // Add event listeners for print
                    window.addEventListener('beforeprint', function() {
                        // Print started
                    });
                    
                    window.addEventListener('afterprint', function() {
                        printCompleted = true;
                        // Close the window after printing
                        setTimeout(function() {
                            window.close();
                        }, 500);
                    });
                    
                    // If user closes without printing, still close
                    setTimeout(function() {
                        if (!printCompleted) {
                            // User might have closed without printing
                            window.close();
                        }
                    }, 5000);
                    
                    // Trigger print
                    window.print();
                };
                
                // Handle window close
                window.onbeforeunload = function() {
                    // Notify parent that window is closing
                    if (window.opener && !window.opener.closed) {
                        try {
                            window.opener.postMessage('printWindowClosed', '*');
                        } catch(e) {}
                    }
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
    
    // Listen for messages from print window
    const handleMessage = (event) => {
        if (event.data === 'printWindowClosed') {
            // Print window closed, go back to POS
            goBackToPos();
        }
    };
    
    window.addEventListener('message', handleMessage);
    
    // Set a timeout to check if print window is closed
    const checkPrintWindow = setInterval(() => {
        if (printWindow && printWindow.closed) {
            clearInterval(checkPrintWindow);
            isPrinting.value = false;
            // Go back to POS after print window is closed
            goBackToPos();
        }
    }, 1000);
    
    // Store the interval for cleanup
    window._printCheckInterval = checkPrintWindow;
};

// Function to go back to POS
const goBackToPos = () => {
    // Clear any intervals
    if (window._printCheckInterval) {
        clearInterval(window._printCheckInterval);
        window._printCheckInterval = null;
    }
    
    // Clean up print window
    if (printWindow && !printWindow.closed) {
        printWindow.close();
        printWindow = null;
    }
    
    // Remove event listener
    window.removeEventListener('message', goBackToPos);
    
    // Navigate back to POS
    router.visit(route('pos.index'));
};

// Manual back to POS
const handleBackToPos = () => {
    if (isPrinting.value) {
        if (confirm('Printing is in progress. Are you sure you want to go back?')) {
            // Close print window if open
            if (printWindow && !printWindow.closed) {
                printWindow.close();
                printWindow = null;
            }
            goBackToPos();
        }
    } else {
        goBackToPos();
    }
};

// Handle print cancel or completion
const handlePrintCancel = () => {
    // If print window is open, close it
    if (printWindow && !printWindow.closed) {
        printWindow.close();
        printWindow = null;
    }
    isPrinting.value = false;
    // Go back to POS
    goBackToPos();
};
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="'Invoice - ' + sale.invoice_no" />
        
        <div class="min-h-screen w-full bg-gray-50 p-4">
            <div class="max-w-4xl mx-auto">
                <!-- Invoice Content -->
                <div id="print-invoice-content" class="bg-white rounded-xl shadow-sm p-8">
                    <!-- Header -->
                    <div class="text-center mb-6">
                        <h1 class="text-2xl font-bold">{{ shop?.name || 'Your Shop' }}</h1>
                        <p class="text-gray-600">{{ shop?.address || 'Shop Address' }}</p>
                        <p class="text-gray-600">Phone: {{ shop?.phone || 'Phone Number' }}</p>
                        <p class="text-gray-600">Email: {{ shop?.email || 'Email Address' }}</p>
                        <h2 class="text-xl font-bold mt-4 text-indigo-700">SALE INVOICE</h2>
                    </div>
                    
                    <!-- Invoice Info -->
                    <div class="grid grid-cols-2 gap-4 mb-6 pb-4 border-b">
                        <div>
                            <p><strong>Invoice No:</strong> {{ sale.invoice_no }}</p>
                            <p><strong>Date:</strong> {{ new Date(sale.sale_date).toLocaleDateString() }}</p>
                            <p><strong>Sale Type:</strong> {{ sale.sale_type }}</p>
                        </div>
                        <div>
                            <p><strong>Customer:</strong> {{ sale.customer?.name || 'Walk-in Customer' }}</p>
                            <p><strong>Phone:</strong> {{ sale.customer?.phone || 'N/A' }}</p>
                            <p><strong>Email:</strong> {{ sale.customer?.email || 'N/A' }}</p>
                        </div>
                    </div>
                    
                    <!-- Items Table -->
                    <table class="w-full mb-6">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left">SL</th>
                                <th class="px-4 py-2 text-left">Product</th>
                                <th class="px-4 py-2 text-right">Qty</th>
                                <th class="px-4 py-2 text-right">Unit Price</th>
                                <th class="px-4 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in sale.details" :key="index" class="border-b">
                                <td class="px-4 py-2">{{ index + 1 }}</td>
                                <td class="px-4 py-2">{{ item.product?.name || 'Product' }}</td>
                                <td class="px-4 py-2 text-right">{{ item.quantity }}</td>
                                <td class="px-4 py-2 text-right">${{ Number(item.unit_price).toFixed(2) }}</td>
                                <td class="px-4 py-2 text-right">${{ Number(item.total_price).toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- Summary -->
                    <div class="flex justify-end">
                        <div class="w-80">
                            <div class="flex justify-between py-1">
                                <span>Subtotal:</span>
                                <span>${{ Number(sale.subtotal).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between py-1 text-red-600">
                                <span>Discount ({{ sale.discount_type }}):</span>
                                <span>-${{ Number(sale.discount).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span>Tax:</span>
                                <span>${{ Number(sale.tax).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span>Shipping:</span>
                                <span>${{ Number(sale.shipping_cost).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between py-2 font-bold border-t">
                                <span>Total:</span>
                                <span class="text-indigo-700">${{ Number(sale.total_amount).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between py-1 text-emerald-600">
                                <span>Paid:</span>
                                <span>${{ Number(sale.paid_amount).toFixed(2) }}</span>
                            </div>
                            <div v-if="sale.due_amount > 0" class="flex justify-between py-1 text-amber-600 font-bold">
                                <span>Due:</span>
                                <span>${{ Number(sale.due_amount).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between py-1 text-xs">
                                <span>Payment Status:</span>
                                <span :class="{
                                    'text-emerald-600': sale.payment_status === 'paid',
                                    'text-amber-600': sale.payment_status === 'partial',
                                    'text-rose-600': sale.payment_status === 'unpaid'
                                }" class="font-medium capitalize">
                                    {{ sale.payment_status }}
                                </span>
                            </div>
                            <div class="flex justify-between py-1 text-xs">
                                <span>Payment Method:</span>
                                <span>{{ sale.payment_method?.replace('_', ' ') || 'N/A' }}</span>
                            </div>
                            <div v-if="sale.notes" class="flex justify-between py-1 text-xs">
                                <span>Notes:</span>
                                <span>{{ sale.notes }}</span>
                            </div>
                            <div class="flex justify-between py-1 text-xs">
                                <span>Sold By:</span>
                                <span>{{ sale.user?.name || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="text-center mt-8 pt-4 border-t">
                        <p class="text-gray-600">Thank you for your business!</p>
                        <p class="text-gray-500 text-sm">This is a computer generated invoice.</p>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="mt-4 text-center space-x-3">
                    <button 
                        @click="printInvoice"
                        :disabled="isPrinting"
                        class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition disabled:opacity-50"
                    >
                        <i v-if="isPrinting" class="fas fa-spinner fa-spin mr-2"></i>
                        <i v-else class="fas fa-print mr-2"></i>
                        {{ isPrinting ? 'Printing...' : 'Print Invoice' }}
                    </button>
                    
                    <button 
                        @click="handleBackToPos"
                        class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition"
                    >
                        <i class="fas fa-arrow-left mr-2"></i> Back to POS
                    </button>
                    
                    <button 
                        v-if="isPrinting"
                        @click="handlePrintCancel"
                        class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition"
                    >
                        <i class="fas fa-times mr-2"></i> Cancel Print
                    </button>
                </div>
                
                <!-- Info message -->
                <div class="mt-4 text-center text-sm text-gray-500">
                    <p v-if="!isPrinting">
                        <i class="fas fa-info-circle mr-1"></i> 
                        Print window will open automatically. After printing, you will be redirected to POS.
                    </p>
                    <p v-else>
                        <i class="fas fa-spinner fa-spin mr-1"></i> 
                        Printing in progress. Please wait...
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Add any additional styles here */
</style>