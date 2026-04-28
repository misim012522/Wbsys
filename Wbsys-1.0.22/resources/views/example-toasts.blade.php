{{-- Simple Step-by-Step Example --}}
{{-- Copy this template and customize for your page --}}

@extends('layouts.app')

@section('title', 'Example Page')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Toast & Real-Time Example</h1>

    <!-- Section 1: Toast Notification Examples -->
    <div class="bg-white rounded-lg border border-slate-200 p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Toast Notification Examples</h2>
        <div class="space-y-2">
            <button onclick="window.showToast.success('This is a success message!')" 
                    class="w-full px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700">
                Show Success Toast
            </button>
            <button onclick="window.showToast.error('This is an error message!')" 
                    class="w-full px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                Show Error Toast
            </button>
            <button onclick="window.showToast.info('This is an info message!')" 
                    class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Show Info Toast
            </button>
            <button onclick="window.showToast.warning('This is a warning message!')" 
                    class="w-full px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
                Show Warning Toast
            </button>
            <button onclick="demoLoadingToast()" 
                    class="w-full px-4 py-2 bg-slate-600 text-white rounded hover:bg-slate-700">
                Show Loading Toast (dismisses in 2 seconds)
            </button>
        </div>
    </div>

    <!-- Section 2: Real-Time Refresh Examples -->
    <div class="bg-white rounded-lg border border-slate-200 p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Real-Time Refresh Examples</h2>
        
        <!-- Example: Queue Count -->
        <div class="mb-6 pb-6 border-b border-slate-200">
            <h3 class="font-medium mb-3">Active Queue Count</h3>
            <div class="bg-slate-50 rounded-lg p-4">
                <p class="text-sm text-slate-600 mb-2">Current Queue:</p>
                <p class="text-3xl font-bold text-emerald-600" id="example-queue-count">0</p>
                <p class="text-xs text-slate-500 mt-2">Updates every 5 seconds</p>
            </div>
        </div>

        <!-- Example: Custom Refresh -->
        <div>
            <h3 class="font-medium mb-3">Custom Data Refresh</h3>
            <div class="bg-slate-50 rounded-lg p-4">
                <p class="text-sm text-slate-600 mb-2">Completed Today:</p>
                <p class="text-3xl font-bold text-slate-700" id="example-completed-count">0</p>
                <p class="text-xs text-slate-500 mt-2">Updates every 5 seconds</p>
            </div>
        </div>
    </div>

    <!-- Section 3: Form with Toast Feedback -->
    <div class="bg-white rounded-lg border border-slate-200 p-6">
        <h2 class="text-lg font-semibold mb-4">Form with Toast Feedback</h2>
        <form onsubmit="demoFormSubmit(event, this)">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Name</label>
                <input type="text" name="name" required 
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Enter your name">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Email</label>
                <input type="email" name="email" required 
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Enter your email">
            </div>
            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Submit Form
            </button>
        </form>
    </div>
</div>

<!-- JavaScript Examples -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Setup real-time refresh for this example
    // Note: Replace '1' with actual office ID in production
    
    // Option 1: Using helper function for queue count
    window.setupQueueRefresh('example-queue-count', 1, 5000);

    // Option 2: Using custom refresh logic
    window.realtimeRefresh.register(
        'example-completed-count',
        '/api/offices/1/completed-today',
        (element, data) => {
            if (data.count !== undefined) {
                element.textContent = data.count;
            }
        },
        5000
    );
});

// Demo: Loading toast that auto-dismisses
function demoLoadingToast() {
    const toastId = window.showToast.loading('Processing...');
    setTimeout(() => {
        window.showToast.dismiss(toastId);
        window.showToast.success('Done!');
    }, 2000);
}

// Demo: Form submission with loading and error handling
function demoFormSubmit(event, form) {
    event.preventDefault();
    
    const toastId = window.showToast.loading('Saving your information...');
    const formData = new FormData(form);
    
    // Simulate API call
    setTimeout(() => {
        window.showToast.dismiss(toastId);
        
        if (Math.random() > 0.3) {
            // Success case (70% chance)
            window.showToast.success('Form submitted successfully! ✓');
            form.reset();
        } else {
            // Error case (30% chance)
            window.showToast.error('Failed to submit form. Please try again.');
        }
    }, 1500);
}
</script>

<!-- Help Text -->
<div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
    <p class="font-medium mb-2">💡 How to use this example:</p>
    <ul class="list-disc list-inside space-y-1">
        <li>Click the buttons to see different toast types</li>
        <li>Watch the numbers update every 5 seconds (real-time refresh)</li>
        <li>Submit the form to see loading state and error handling</li>
        <li>Copy the code patterns to your own pages</li>
        <li>Replace office ID '1' with your actual office ID</li>
    </ul>
</div>

@endsection
