# Quick Start Guide: Toast Notifications & Real-Time Refresh

## 1. Toast Notifications

### Automatic Display for Success Messages

Just use Laravel's session helper as usual in your controllers:

```php
return back()->with('success', 'Profile updated successfully!');
return back()->with('error', 'Failed to save changes');
```

These messages automatically display as toast popups on the page.

## 2. Manual Toast Triggers

### In Blade Templates

```blade
<button onclick="window.showToast.success('Item saved!')">Save</button>
```

### In JavaScript/AJAX Responses

```javascript
// After a successful action
axios.post('/api/save-data', data)
    .then(response => {
        window.showToast.success('Data saved successfully!');
    })
    .catch(error => {
        window.showToast.error('Failed to save data');
    });
```

## 3. Real-Time Refresh Examples

### Example 1: Update Queue Count Every 5 Seconds

**Blade Template:**
```blade
<div class="stat-card">
    <p>Active Queue</p>
    <p id="queue-count" class="text-3xl font-bold">0</p>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    window.setupQueueRefresh('queue-count', {{ $office->id }}, 5000);
});
</script>
```

**What it does:**
- Fetches `/api/offices/{{ $office->id }}/queue-count` every 5 seconds
- Updates the element with the new count
- Pauses when the browser tab loses focus

### Example 2: Live Queue List

**Blade Template:**
```blade
<div id="queue-list-container" class="space-y-2">
    <!-- Auto-updated queue list will appear here -->
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    window.setupListRefresh(
        'queue-list-container', 
        '/api/offices/{{ $office->id }}/queue-list',
        3000
    );
});
</script>
```

**What it does:**
- Fetches HTML from the API endpoint every 3 seconds
- Updates the container with the returned HTML
- The component automatically displays queue entries with status badges

### Example 3: Multiple Real-Time Elements

**Blade Template:**
```blade
<div class="dashboard-grid">
    <div class="stat">
        <span>Queue</span>
        <p id="queue-count">0</p>
    </div>
    
    <div class="stat">
        <span>Appointments Today</span>
        <p id="appointments-count">0</p>
    </div>
    
    <div class="stat">
        <span>Completed</span>
        <p id="completed-count">0</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const officeId = {{ $office->id }};
    
    window.setupQueueRefresh('queue-count', officeId, 5000);
    window.setupAppointmentsRefresh('appointments-count', officeId, 5000);
    
    window.realtimeRefresh.register(
        'completed-count',
        `/api/offices/${officeId}/completed-today`,
        (element, data) => element.textContent = data.count,
        5000
    );
});
</script>
```

## 4. Logout with Toast

The logout button already shows a toast:

```blade
<form method="POST" action="{{ route('logout') }}" id="logout-form">
    @csrf
    <button 
        type="submit"
        onclick="window.showToast.success('Logged out successfully. Redirecting...'); 
                 setTimeout(() => this.form.submit(), 500);"
    >
        Log out
    </button>
</form>
```

## 5. Creating Custom Toast Types

```javascript
// Different toast types
window.showToast.success('Success message', 3000);      // 3 second duration
window.showToast.error('Error message', 4000);           // 4 second duration
window.showToast.info('Info message', 3000);
window.showToast.warning('Warning message', 3500);

// Loading toast (doesn't auto-dismiss)
const id = window.showToast.loading('Processing...');
// Later, dismiss it
window.showToast.dismiss(id);
```

## 6. Controlling Real-Time Refresh

```javascript
// Pause all real-time updates
window.realtimeRefresh.pauseAll();

// Resume all real-time updates
window.realtimeRefresh.resumeAll();

// Unregister a specific element
window.realtimeRefresh.unregister('queue-count');

// Manually refresh one element
window.realtimeRefresh.refresh('queue-count', '/api/offices/1/queue-count', callback);

// Stop everything
window.realtimeRefresh.destroy();
```

## 7. API Endpoints Available

Your application now has these API endpoints for real-time data:

| Endpoint | Method | Response |
|----------|--------|----------|
| `/api/offices/{id}/queue-count` | GET | `{ "count": 5 }` |
| `/api/offices/{id}/appointments-today` | GET | `{ "count": 3 }` |
| `/api/offices/{id}/completed-today` | GET | `{ "count": 12 }` |
| `/api/offices/{id}/queue-list` | GET | `{ "html": "..." }` |
| `/api/offices/{id}/appointments-list` | GET | `{ "html": "..." }` |

## 8. Customizing Toast Position

Edit `resources/js/app.js` to change where toasts appear:

```javascript
new Toaster({
    position: 'top-right',      // top-left, top-center, top-right, bottom-left, bottom-center, bottom-right
    richColors: true,
    expand: false,
    closeButton: true,
    duration: 4000,
});
```

## 9. Browser Focus Handling

Real-time refresh automatically pauses when:
- User switches to another browser tab
- Browser window loses focus
- Page becomes hidden

And resumes when:
- User returns to the tab
- Browser window gets focus

This saves bandwidth and improves performance!

## 10. Common Patterns

### Pattern: Save with Toast Confirmation

```blade
<form onsubmit="handleFormSubmit(event, this)">
    <input type="text" name="name" required>
    <button type="submit">Save</button>
</form>

<script>
function handleFormSubmit(event, form) {
    event.preventDefault();
    
    const formData = new FormData(form);
    const toastId = window.showToast.loading('Saving...');
    
    axios.post(form.action, formData)
        .then(response => {
            window.showToast.dismiss(toastId);
            window.showToast.success('Saved successfully!');
            form.reset();
        })
        .catch(error => {
            window.showToast.dismiss(toastId);
            window.showToast.error(error.response?.data?.message || 'Failed to save');
        });
}
</script>
```

### Pattern: Polling with Alternative Refresh

```javascript
// Refresh every 3 seconds when user is actively looking at the page
window.setupQueueRefresh('queue-count', officeId, 3000);

// Stop refreshing after 5 minutes of inactivity
setTimeout(() => {
    window.showToast.info('Auto-refresh paused due to inactivity');
    window.realtimeRefresh.pauseAll();
}, 5 * 60 * 1000);
```

## Next Steps

1. Update your controllers to return JSON responses with `message` property for automatic toasts
2. Add real-time refresh to your key dashboard views
3. Test the smooth animations and responsiveness
4. Customize colors and positions to match your brand
