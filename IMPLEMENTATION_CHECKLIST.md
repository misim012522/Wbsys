# Implementation Checklist

Use this checklist when implementing toast notifications and real-time refresh on your pages.

## Toast Notifications

### Session Messages (Automatic)
- [ ] Controller returns with session: `return back()->with('success', 'Message')`
- [ ] Toast automatically appears on page load
- [ ] Works with all session types: `success`, `error`, `info`, `status`

### Manual Toasts
- [ ] Trigger on button click: `onclick="window.showToast.success('Done!')"`
- [ ] Use in JavaScript for form submissions
- [ ] Handle errors: `window.showToast.error('Failed')`

### Loading Toasts
- [ ] Show loading toast before async operation: `const id = window.showToast.loading('Processing...')`
- [ ] Dismiss when done: `window.showToast.dismiss(id)`

## Real-Time Refresh Setup

### Queue Count
- [ ] Add element with ID: `<p id="queue-count">0</p>`
- [ ] Initialize in script: `window.setupQueueRefresh('queue-count', {{ $office->id }}, 5000)`
- [ ] Verify API endpoint: `/api/offices/{id}/queue-count`

### Appointments Count  
- [ ] Add element with ID: `<p id="appointments-count">0</p>`
- [ ] Initialize in script: `window.setupAppointmentsRefresh('appointments-count', {{ $office->id }}, 5000)`
- [ ] Verify API endpoint: `/api/offices/{id}/appointments-today`

### Queue List
- [ ] Add container: `<div id="queue-list"></div>`
- [ ] Initialize: `window.setupListRefresh('queue-list', '/api/offices/{{ $office->id }}/queue-list', 5000)`
- [ ] Verify API endpoint returns `{ "html": "..." }`

### Appointments List
- [ ] Add container: `<div id="appointments-list"></div>`
- [ ] Initialize: `window.setupListRefresh('appointments-list', '/api/offices/{{ $office->id }}/appointments-list', 5000)`
- [ ] Verify API endpoint returns `{ "html": "..." }`

## Pages to Update

### Admin Dashboard
- [ ] Add queue count display with refresh
- [ ] Add appointments count with refresh
- [ ] Add completed count with refresh
- [ ] Display per-office statistics

### Serve Queue Page
- [ ] Real-time queue list updates
- [ ] Current serving queue entry updates
- [ ] Remaining queue count updates

### Office Settings
- [ ] Success/error toasts on save
- [ ] Loading state during save
- [ ] Validation error toasts

### User Management
- [ ] Success toast on user approval
- [ ] Error toast on user creation failure
- [ ] Delete confirmation with toast

### Reports Page
- [ ] Real-time data updates
- [ ] Export functionality feedback toast
- [ ] Loading state for report generation

## API Endpoints Checklist

### Required Endpoints
- [ ] `/api/offices/{id}/queue-count` - Returns `{ "count": number }`
- [ ] `/api/offices/{id}/appointments-today` - Returns `{ "count": number }`
- [ ] `/api/offices/{id}/completed-today` - Returns `{ "count": number }`
- [ ] `/api/offices/{id}/queue-list` - Returns `{ "html": string }`
- [ ] `/api/offices/{id}/appointments-list` - Returns `{ "html": string }`

### Authorization
- [ ] All endpoints require `auth` middleware
- [ ] All endpoints check `authorize('view', $office)`
- [ ] Consider role-based access (admin vs staff)

## Testing Checklist

### Toast Notifications
- [ ] [ ] Success toast appears and auto-hides
- [ ] [ ] Error toast appears and auto-hides
- [ ] [ ] Info toast appears and auto-hides
- [ ] [ ] Warning toast appears and auto-hides
- [ ] [ ] Loading toast never auto-hides
- [ ] [ ] Loading toast dismisses when manually dismissed
- [ ] [ ] Multiple toasts can show simultaneously

### Real-Time Refresh
- [ ] [ ] Data updates appear without page refresh
- [ ] [ ] Updates happen at set intervals (e.g., every 5 seconds)
- [ ] [ ] Refresh pauses when tab is not active
- [ ] [ ] Refresh resumes when tab becomes active again
- [ ] [ ] Multiple elements refresh simultaneously
- [ ] [ ] No JavaScript errors in console

### Performance
- [ ] [ ] No network requests sent when tab is inactive
- [ ] [ ] Refresh interval is not too aggressive (not < 2 seconds)
- [ ] [ ] No memory leaks when navigating away

### User Experience
- [ ] [ ] Toasts don't interfere with page layout
- [ ] [ ] Toast messages are clear and helpful
- [ ] [ ] Refresh updates feel smooth, not jarring
- [ ] [ ] No duplicate toasts shown

## Browser Testing

- [ ] [ ] Chrome/Chromium - Full functionality
- [ ] [ ] Firefox - Full functionality
- [ ] [ ] Safari - Full functionality
- [ ] [ ] Edge - Full functionality
- [ ] [ ] Mobile browsers - Touch-friendly
- [ ] [ ] Small screens - Responsive layout

## Documentation Checklist

- [ ] [ ] Added comments explaining real-time setup
- [ ] [ ] Documented custom refresh intervals
- [ ] [ ] Listed all API endpoints used
- [ ] [ ] Provided examples for other developers
- [ ] [ ] Noted any special considerations

## Optimization Checklist

- [ ] [ ] Refresh intervals are appropriate (not too frequent)
- [ ] [ ] API endpoints are optimized (return minimal data)
- [ ] [ ] Components render efficiently
- [ ] [ ] No unnecessary re-renders or updates
- [ ] [ ] Proper error handling in API responses

## Deployment Checklist

- [ ] [ ] Vite build passes without errors: `npm run build`
- [ ] [ ] All dependencies installed: `npm install`
- [ ] [ ] API routes registered in `routes/web.php`
- [ ] [ ] ApiController is properly namespaced
- [ ] [ ] All views are accessible
- [ ] [ ] Database migrations run successfully
- [ ] [ ] No console errors in production

## Post-Launch Monitoring

- [ ] [ ] Monitor API endpoint performance
- [ ] [ ] Check for any JavaScript errors in logs
- [ ] [ ] Verify real-time updates are working for all users
- [ ] [ ] Get user feedback on toast notifications
- [ ] [ ] Monitor bandwidth usage for API calls
- [ ] [ ] Check that toasts are displaying correctly on all devices

## Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| Toasts not showing | Rebuild with `npm run build` |
| API returning 401 | User not authenticated, check auth middleware |
| Real-time not updating | Verify API endpoint returns correct JSON format |
| Too many API requests | Increase refresh interval (e.g., 5000 instead of 2000) |
| Memory leak warnings | Check that intervals are properly cleared on page leave |

---

**Last Updated**: March 12, 2026
**Status**: Ready for Implementation
**Difficulty**: Low to Medium
