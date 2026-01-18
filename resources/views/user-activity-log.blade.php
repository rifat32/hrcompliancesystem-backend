<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Activity Logs</title>

    <!-- Bootstrap 5.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        .form-control, .form-select {
            min-width: 150px;
        }
        th, td {
            vertical-align: middle !important;
        }
        .log-box {
            max-width: 250px;
            max-height: 100px;
            overflow: auto;
            white-space: pre-wrap;
        }

        /* Shrink pagination buttons without vendor changes */
        .pagination {
            font-size: 0.85rem;
        }
        .pagination .page-link {
            padding: 0.25rem 0.5rem;
            border-radius: 0.2rem;
        }
        .pagination .page-item:not(:last-child) {
            margin-right: 0.25rem;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h3 class="mb-4">Activity Log</h3>

   <!-- Filter Form -->
    <form method="GET" action="{{ route('activity-log') }}" class="row g-3 mb-4">
        <div class="col-md-2">
            <input type="text" class="form-control" name="id" placeholder="Log ID" value="{{ request('id') }}" />
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control" name="user_id" placeholder="User ID" value="{{ request('user_id') }}" />
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control" name="api_url" placeholder="API URL" value="{{ request('api_url') }}" />
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control" name="ip_address" placeholder="IP Address" value="{{ request('ip_address') }}" />
        </div>
        <div class="col-md-2">
            <select class="form-select" name="request_method">
                <option value="">Method</option>
                @foreach(['GET','POST','PUT','DELETE'] as $method)
                    <option value="{{ $method }}" @selected(request('request_method') == $method)>{{ $method }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="status_code">
                <option value="">Status</option>
                @foreach([200, 201, 400, 401, 403, 404, 422, 500] as $code)
                    <option value="{{ $code }}" @selected(request('status_code') == $code)>{{ $code }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="is_error">
                <option value="">Is Error?</option>
                <option value="1" @selected(request('is_error') === '1')>Yes</option>
                <option value="0" @selected(request('is_error') === '0')>No</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" name="date" value="{{ request('date') }}" />
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control" name="business_id" placeholder="Business ID" value="{{ request('business_id') }}" />
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-success w-100">Filter</button>
        </div>
        <div class="col-md-2">
            <a href="{{ route('activity-log') }}" class="btn btn-secondary w-100">Reset</a>
        </div>
    </form>

    <!-- Log Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Test</th>
                    <th>User</th>
                    <th>User ID</th>
                    <th>API URL</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>IP</th>
                    <th>Date</th>
                    <th>Message</th>
                    <th>Fields</th>
                    <th>Line</th>
                    <th>File</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activity_logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td><a href="{{ route('api-test', $log->id) }}" class="btn btn-sm btn-primary" target="_blank">TEST</a></td>
                        <td>@if ($log->ERRuser){{ $log->ERRuser->first_Name }} {{ $log->ERRuser->last_Name }}@endif</td>
                        <td>{{ $log->user_id }}</td>
                        <td><div class="log-box">{{ $log->api_url }}</div></td>
                        <td>{{ $log->request_method }}</td>
                        <td>{{ $log->status_code }}</td>
                        <td>{{ $log->ip_address }}</td>
                        <td>{{ $log->created_at }}</td>
                        <td><div class="log-box">{{ $log->message }}</div></td>
                        <td><div class="log-box">{{ $log->fields }}</div></td>
                        <td>{{ $log->line }}</td>
                        <td>{{ $log->file }}</td>
                    </tr>
                @empty
                    <tr><td colspan="13" class="text-center">No activity logs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center">
        {{ $activity_logs->withQueryString()->links() }}

    </div>
</div>

<!-- Bootstrap 5.3 JS Bundle CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
