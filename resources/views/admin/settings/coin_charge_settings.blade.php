@extends('admin.layouts.app')
@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h1 class="mb-0 h6">{{ translate('Coin Charge Settings') }}</h1>
                </div>
                <div class="card-body">
                    <form action="{{ route('settings.update') }}" method="POST">
                        @csrf
                        <div class="form-group row">
                            <label class="col-sm-3 col-from-label">{{ translate('Coin Charge Configuration') }}</label>
                            <div class="col-sm-9">
                                <div class="row gutters-10">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ translate('Express Interest') }}</label>
                                        <input type="hidden" name="types[]" value="feature_coin_cost_express_interest">
                                        <input type="number" min="0" name="feature_coin_cost_express_interest" class="form-control" value="{{ get_setting('feature_coin_cost_express_interest', 1) }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ translate('Shortlist') }}</label>
                                        <input type="hidden" name="types[]" value="feature_coin_cost_shortlist">
                                        <input type="number" min="0" name="feature_coin_cost_shortlist" class="form-control" value="{{ get_setting('feature_coin_cost_shortlist', 5) }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ translate('Contact View') }}</label>
                                        <input type="hidden" name="types[]" value="feature_coin_cost_contact_view">
                                        <input type="number" min="0" name="feature_coin_cost_contact_view" class="form-control" value="{{ get_setting('feature_coin_cost_contact_view', 1) }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ translate('Profile Image View') }}</label>
                                        <input type="hidden" name="types[]" value="feature_coin_cost_profile_image_view">
                                        <input type="number" min="0" name="feature_coin_cost_profile_image_view" class="form-control" value="{{ get_setting('feature_coin_cost_profile_image_view', 1) }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ translate('Gallery Image View') }}</label>
                                        <input type="hidden" name="types[]" value="feature_coin_cost_gallery_image_view">
                                        <input type="number" min="0" name="feature_coin_cost_gallery_image_view" class="form-control" value="{{ get_setting('feature_coin_cost_gallery_image_view', 1) }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
