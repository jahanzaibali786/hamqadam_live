@extends('admin.layouts.app')
@section('content')
<div class="container-fluid py-4">
  <div class="alert alert-warning" role="alert">
    <h1 class="h4">Referral Settings — Database Setup Required</h1>
    <p>The referral reward tables have not been installed yet. Run the pending Laravel migrations before configuring registration and paid-package activation rewards.</p>
    <pre class="bg-white p-3 border rounded">php artisan migrate --force
php artisan optimize:clear
php artisan view:clear</pre>
    <p class="mb-0">Your existing referrals are not deleted. If the tables already exist, check Laravel logs and database connection.</p>
  </div>
</div>
@endsection
