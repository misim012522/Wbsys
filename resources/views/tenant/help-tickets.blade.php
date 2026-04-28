@extends('layouts.office')

@section('content')
<div class="container-fluid py-4 min-vh-100" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); color: #eef2ff;">
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-4 fw-bold mb-2" style="background: linear-gradient(to right, #818cf8, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                Demo: Help Desk Tickets
            </h1>
            <p class="lead opacity-75">Testing automated migrations with a ticketing system.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- New Ticket Form -->
        <div class="col-md-4">
            <div class="card border-0 shadow-lg h-100" style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1) !important;">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-4 d-flex align-items-center">
                        <span class="p-2 rounded-3 me-2" style="background: #4338ca;">
                            <i class="bi bi-plus-circle"></i>
                        </span>
                        Submit New Ticket
                    </h5>
                    <form action="{{ route('tenant.help-tickets.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label opacity-75">Subject</label>
                            <input type="text" name="subject" class="form-control bg-transparent border-secondary text-white focus-indigo" required placeholder="e.g. Connection issue">
                        </div>
                        <div class="mb-4">
                            <label class="form-label opacity-75">Description</label>
                            <textarea name="description" class="form-control bg-transparent border-secondary text-white" rows="4" required placeholder="Describe the problem..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-indigo w-100 py-2 fw-bold">
                            Submit Ticket
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tickets List -->
        <div class="col-md-8">
            <div class="card border-0 shadow-lg" style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1) !important;">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-4 d-flex align-items-center">
                        <span class="p-2 rounded-3 me-2" style="background: #6d28d9;">
                            <i class="bi bi-ticket-perforated"></i>
                        </span>
                        Recent Tickets
                    </h5>
                    
                    @if($tickets->isEmpty())
                        <div class="text-center py-5 opacity-50">
                            <i class="bi bi-inbox display-1 mb-3 d-block"></i>
                            <p>No tickets submitted yet. This is a clean database!</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover text-white">
                                <thead class="opacity-50 border-0">
                                    <tr>
                                        <th>Date</th>
                                        <th>Subject</th>
                                        <th>User</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tickets as $ticket)
                                    <tr class="border-secondary border-opacity-25">
                                        <td class="align-middle fs-7 opacity-75">{{ $ticket->created_at->format('M d, H:i') }}</td>
                                        <td class="align-middle">
                                            <div class="fw-bold">{{ $ticket->subject }}</div>
                                            <small class="opacity-50">{{ Str::limit($ticket->description, 50) }}</small>
                                        </td>
                                        <td class="align-middle opacity-75">{{ $ticket->user_name }}</td>
                                        <td class="align-middle">
                                            <span class="badge rounded-pill {{ $ticket->status === 'open' ? 'bg-info' : 'bg-success' }}">
                                                {{ strtoupper($ticket->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .btn-indigo {
        background: #4338ca;
        color: white;
        border: none;
    }
    .btn-indigo:hover {
        background: #3730a3;
        color: white;
    }
    .focus-indigo:focus {
        border-color: #818cf8 !important;
        box-shadow: 0 0 0 0.25rem rgba(129, 140, 248, 0.25) !important;
    }
    .fs-7 { font-size: 0.85rem; }
    .table-hover tbody tr:hover {
        background: rgba(255, 255, 255, 0.05);
    }
</style>
@endsection
