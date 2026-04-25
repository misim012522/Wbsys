# Toast Notifications & Real-Time Refresh Documentation

## Toast Notifications

Toast notifications are now automatically displayed for all session messages (success, error, info, status). They appear as elegant popups in the top-right corner of the screen.

### Automatic Display

Session messages are automatically converted to toast notifications:

```php
// In your controller
return back()->with('success', 'Your message here');
return back()->with('error', 'An error occurred');
return back()->with('info', 'Information message');
return back()->with('status', 'Status update');
```

### Manual Display in JavaScript

You can also trigger toasts manually from JavaScript:

```javascript
// Success toast
window.showToast.success('Operation completed successfully!');

// Error toast
window.showToast.error('Something went wrong');

// Info toast
window.showToast.info('Here is some information');

// Warning toast
window.showToast.warning('Please be careful');

// Loading toast (returns ID for later dismissal)
const id = window.showToast.loading('Processing...');

// Dismiss a toast
window.showToast.dismiss(id);

// Promise-based toast
window.showToast.promise(
    Promise.resolve('Done!'),
    {
        loading: 'Loading...',
        success: 'Success!',
        error: 'Error!'
    }
);
```

### Axios Automatic Toasts

If your backend returns JSON responses with a `message` property, toasts are automatically shown:

```javascript
// Successful response with automatic toast
axios.post('/api/endpoint', data); // Shows success toast if response has message

// Error response with automatic toast
// Will show error toast if error.response.data.message exists
```

## Real-Time Data Refresh

The real-time refresh system allows you to automatically poll for data updates without requiring page refreshes.

### Basic Usage

#### Refresh Queue Count

In your Blade template:

```blade
<p id="queue-count">0</p>

<script>
window.setupQueueRefresh('queue-count', {{ $officeId }}, 5000);
</script>
```

This will automatically update the queue count every 5 seconds.

#### Refresh Appointments Count

```blade
<p id="appointments-today">0</p>

<script>
window.setupAppointmentsRefresh('appointments-today', {{ $officeId }}, 5000);
</script>
```

#### Refresh a List

For updating larger content sections:

```blade
<div id="queue-list"></div>

<script>
window.setupListRefresh('queue-list', '/api/offices/{{ $officeId }}/queue-list', 5000);
</script>
```

This expects the API to return JSON with an `html` property:

```json
{
    "html": "<div>Queue entry 1</div><div>Queue entry 2</div>"
}
```

### Advanced Usage

For custom refresh logic:

```javascript
window.realtimeRefresh.register(
    'element-id',
    '/api/endpoint',
    (element, data) => {
        // Custom update logic
        element.textContent = data.customField;
    },
    5000 // interval in milliseconds
);
```

### Controlling Refresh

```javascript
// Pause all refreshes
window.realtimeRefresh.pauseAll();

// Resume all refreshes
window.realtimeRefresh.resumeAll();

// Manually trigger refresh for specific element
window.realtimeRefresh.refresh('element-id', '/api/endpoint', updateCallback);

// Stop all refreshes
window.realtimeRefresh.destroy();

// Unregister specific element
window.realtimeRefresh.unregister('element-id');
```

### Smart Focus Handling

The system automatically pauses refreshes when:
- The browser tab loses focus
- The page is hidden

And resumes when:
- The browser tab regains focus
- The page becomes visible again

This saves bandwidth and improves performance on unfocused tabs.

## Example: Complete Dashboard with Real-Time Updates

```blade
@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <p class="text-sm text-slate-500 mb-1">Active Queue</p>
        <p class="text-3xl font-bold text-emerald-600" id="queue-count">0</p>
    </div>
    
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <p class="text-sm text-slate-500 mb-1">Today's Appointments</p>
        <p class="text-3xl font-bold text-blue-600" id="appointments-count">0</p>
    </div>
</div>

<div id="queue-list" class="space-y-2">
    <!-- Will be auto-updated -->
</div>

<script>
    // Setup real-time updates (5 second interval)
    window.setupQueueRefresh('queue-count', {{ $office->id }}, 5000);
    window.setupAppointmentsRefresh('appointments-count', {{ $office->id }}, 5000);
    window.setupListRefresh('queue-list', '/api/offices/{{ $office->id }}/queue-list', 5000);
</script>
@endsection
```

## API Endpoints Required

For real-time refresh to work, your backend should provide these endpoints:

### Queue Count
`GET /api/offices/{officeId}/queue-count`

Response:
```json
{
    "count": 5
}
```

### Appointments Count
`GET /api/offices/{officeId}/appointments-today`

Response:
```json
{
    "count": 3
}
```

### Queue List
`GET /api/offices/{officeId}/queue-list`

Response:
```json
{
    "html": "<div class='queue-item'>...</div>"
}
```

## Logout Toast

The logout button in the header automatically displays a toast notification:

```blade
<button 
    onclick="window.showToast.success('Logged out successfully. Redirecting...'); 
             setTimeout(() => this.form.submit(), 500);"
>
    Log out
</button>
```

## Styling

Toast notifications use Sonner's default styling which integrates with Tailwind CSS. To customize appearance, modify the Toaster configuration in `resources/js/app.js`:

```javascript
new Toaster({
    position: 'top-right',        // Position: top-left, top-center, top-right, bottom-left, bottom-center, bottom-right
    richColors: true,              // Use rich colors for different toast types
    expand: false,                 // Expand toasts to full width
    closeButton: true,             // Show close button
    duration: 4000,                // Default duration in milliseconds
});
```

## Browser Compatibility

- Chrome/Edge: Full support
- Firefox: Full support
- Safari: Full support
- IE11: Not supported (requires polyfills)
