<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report — {{ $office->name }} — {{ $date }}</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; font-size: 14px; line-height: 1.5; color: #1e293b; max-width: 900px; margin: 0 auto; padding: 24px; }
        h1 { font-size: 1.5rem; margin-bottom: 4px; }
        .meta { color: #64748b; font-size: 0.875rem; margin-bottom: 24px; }
        h2 { font-size: 1.125rem; margin-top: 24px; margin-bottom: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th, td { text-align: left; padding: 8px 12px; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; color: #64748b; }
        .summary { display: flex; gap: 24px; flex-wrap: wrap; margin-bottom: 24px; }
        .summary-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px; min-width: 140px; }
        .summary-box strong { display: block; font-size: 1.25rem; color: #166534; }
        .summary-box span { font-size: 0.75rem; color: #15803d; }
        .footer { margin-top: 32px; font-size: 0.75rem; color: #94a3b8; }
        @media print { body { padding: 16px; } .no-print { display: none; } }
    </style>
</head>
<body>
    <h1>QueueLess — Office Report</h1>
    <p class="meta">{{ $office->name }} · Report date: {{ $date }} · Generated: {{ now()->format('M j, Y g:i A') }}</p>

    <div class="summary">
        <div class="summary-box">
            <strong>{{ $queueEntries->count() }}</strong>
            <span>Queue entries</span>
        </div>
        <div class="summary-box">
            <strong>{{ $appointments->count() }}</strong>
            <span>Appointments</span>
        </div>
        @foreach($queueByStatus as $status => $count)
        <div class="summary-box">
            <strong>{{ $count }}</strong>
            <span>Queue — {{ $status }}</span>
        </div>
        @endforeach
        @foreach($appointmentsByStatus as $status => $count)
        <div class="summary-box">
            <strong>{{ $count }}</strong>
            <span>Appt — {{ $status }}</span>
        </div>
        @endforeach
    </div>

    <h2>Queue ({{ $date }})</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Type</th>
                <th>Contact</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($queueEntries as $e)
            <tr>
                <td>{{ $e->queue_number }}</td>
                <td>{{ $e->display_name }}</td>
                <td>{{ $e->service_type ?? '—' }}</td>
                <td>{{ $e->guest_email ?? $e->guest_phone ?? '—' }}</td>
                <td>{{ $e->status }}</td>
            </tr>
            @empty
            <tr><td colspan="5">No queue entries.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Appointments ({{ $date }})</h2>
    <table>
        <thead>
            <tr>
                <th>Time</th>
                <th>Name</th>
                <th>Type</th>
                <th>Contact</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($appointments as $a)
            <tr>
                <td>{{ \Carbon\Carbon::parse($a->appointment_time)->format('g:i A') }}</td>
                <td>{{ $a->display_name }}</td>
                <td>{{ $a->appointment_type ?? '—' }}</td>
                <td>{{ $a->guest_email ?? $a->guest_phone ?? '—' }}</td>
                <td>{{ $a->status }}</td>
            </tr>
            @empty
            <tr><td colspan="5">No appointments.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">QueueLess — Smart appointment & queue management. Report generated on {{ now()->toDateTimeString() }}.</p>

    <p class="no-print" style="margin-top: 24px;">
        <button type="button" onclick="window.print()" style="padding: 8px 16px; background: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px;">Print / Save as PDF</button>
    </p>
</body>
</html>
