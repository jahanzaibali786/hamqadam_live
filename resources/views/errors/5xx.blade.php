{{-- Generic status-class fallback. --}}
@include('errors.page', ['status' => (int) (isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500)])
