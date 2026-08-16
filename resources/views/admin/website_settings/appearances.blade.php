@extends('admin.layouts.app')
@section('content')
<div class="row">
    <div class="col-lg-11 mx-auto">
      <div class="card">
        <div class="card-header">
          <h6 class="fw-600 mb-0">{{ translate('General') }}</h6>
        </div>
        <div class="card-body">
          <form action="{{ route('settings.update') }}" method="POST">
            @csrf
              <div class="form-group row">
                  <label class="col-md-3 col-from-label">{{translate('Frontend Website Name')}}</label>
                  <div class="col-md-8">
                      <input type="hidden" name="types[]" value="website_name">
                    <input type="text" name="website_name" class="form-control" placeholder="{{ translate('Website Name') }}" value="{{ get_setting('website_name') }}">
                  </div>
              </div>
              <div class="form-group row">
                  <label class="col-md-3 col-from-label">{{translate('Site Motto')}}</label>
                    <div class="col-md-8">
                        <input type="hidden" name="types[]" value="site_motto">
                      <input type="text" name="site_motto" class="form-control" placeholder="{{ translate('Best Matrimonial Website') }}" value="{{  get_setting('site_motto') }}">
                    </div>
              </div>
              <div class="form-group row">
                  <label class="col-md-3 col-from-label">{{ translate('Site Icon') }}</label>
                  <div class="col-md-8">
                      <div class="input-group " data-toggle="aizuploader" data-type="image">
                        <div class="input-group-prepend">
                          <div class="input-group-text bg-soft-secondary">{{ translate('Browse') }}</div>
                        </div>
                        <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                                      <input type="hidden" name="types[]" value="site_icon">
                        <input type="hidden" name="site_icon" value="{{ get_setting('site_icon') }}" class="selected-files">
                      </div>
                      <div class="file-preview box"></div>
                      <small class="text-muted">{{ translate('Website favicon. 32x32 .png') }}</small>
                  </div>
              </div>
              <div class="form-group row">
                  <label class="col-md-3 col-from-label">{{translate('Website Base Color')}}</label>
                  <div class="col-md-8">
                    <input type="hidden" name="types[]" value="base_color">
                    <input type="text" name="base_color" class="form-control" placeholder="#377dff" value="{{ get_setting('base_color') }}">
                    <small class="text-muted">{{ translate('Hex Color Code') }}</small>
                  </div>
              </div>
              <div class="form-group row">
                  <label class="col-md-3 col-from-label">{{translate('Website Base Hover Color')}}</label>
                  <div class="col-md-8">
                      <input type="hidden" name="types[]" value="base_hov_color">
                      <input type="text" name="base_hov_color" class="form-control" placeholder="#377dff" value="{{  get_setting('base_hov_color') }}">
                      <small class="text-muted">{{ translate('Hex Color Code') }}</small>
                  </div>
              </div>
              <div class="form-group row">
                  <label class="col-md-3 col-from-label">{{translate('Website Secondary Color')}}</label>
                  <div class="col-md-8">
                      <input type="hidden" name="types[]" value="secondary_color">
                      <input type="text" name="secondary_color" class="form-control" placeholder="#377dff" value="{{  get_setting('secondary_color') }}">
                      <small class="text-muted">{{ translate('Hex Color Code').'. '.translate('Gradient color will be generated with base color and secondary color.') }}</small>
                  </div>
              </div>
              <div class="form-group row">
                  <label class="col-md-3 col-from-label">{{ translate('Brand Palette') }}</label>
                  <div class="col-md-8">
                      <div class="row gutters-10">
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Primary') }}</label>
                              <input type="hidden" name="types[]" value="frontend_primary_color">
                              <input type="color" name="frontend_primary_color" class="form-control" value="{{ get_setting('frontend_primary_color', '#FF3B6B') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Primary Dark') }}</label>
                              <input type="hidden" name="types[]" value="frontend_primary_dark">
                              <input type="color" name="frontend_primary_dark" class="form-control" value="{{ get_setting('frontend_primary_dark', '#E92B58') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Primary Light') }}</label>
                              <input type="hidden" name="types[]" value="frontend_primary_light">
                              <input type="color" name="frontend_primary_light" class="form-control" value="{{ get_setting('frontend_primary_light', '#FF7A9B') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Accent') }}</label>
                              <input type="hidden" name="types[]" value="frontend_accent_color">
                              <input type="color" name="frontend_accent_color" class="form-control" value="{{ get_setting('frontend_accent_color', '#FF3B6B') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Gold') }}</label>
                              <input type="hidden" name="types[]" value="frontend_gold_color">
                              <input type="color" name="frontend_gold_color" class="form-control" value="{{ get_setting('frontend_gold_color', '#C9A24B') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Gold Light') }}</label>
                              <input type="hidden" name="types[]" value="frontend_gold_light_color">
                              <input type="color" name="frontend_gold_light_color" class="form-control" value="{{ get_setting('frontend_gold_light_color', '#FFD9E4') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Light Background') }}</label>
                              <input type="hidden" name="types[]" value="frontend_light_background">
                              <input type="color" name="frontend_light_background" class="form-control" value="{{ get_setting('frontend_light_background', '#FFFFFF') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Light Surface') }}</label>
                              <input type="hidden" name="types[]" value="frontend_light_surface">
                              <input type="color" name="frontend_light_surface" class="form-control" value="{{ get_setting('frontend_light_surface', '#FAFAFA') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Light Surface Alt') }}</label>
                              <input type="hidden" name="types[]" value="frontend_light_surface_alt">
                              <input type="color" name="frontend_light_surface_alt" class="form-control" value="{{ get_setting('frontend_light_surface_alt', '#F3F3F3') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Light Text Primary') }}</label>
                              <input type="hidden" name="types[]" value="frontend_light_text_primary">
                              <input type="color" name="frontend_light_text_primary" class="form-control" value="{{ get_setting('frontend_light_text_primary', '#FF3B6B') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Light Text Secondary') }}</label>
                              <input type="hidden" name="types[]" value="frontend_light_text_secondary">
                              <input type="color" name="frontend_light_text_secondary" class="form-control" value="{{ get_setting('frontend_light_text_secondary', '#C23557') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Light Text Hint') }}</label>
                              <input type="hidden" name="types[]" value="frontend_light_text_hint">
                              <input type="color" name="frontend_light_text_hint" class="form-control" value="{{ get_setting('frontend_light_text_hint', '#FF8FAB') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Light Input Text') }}</label>
                              <input type="hidden" name="types[]" value="frontend_light_input_text">
                              <input type="color" name="frontend_light_input_text" class="form-control" value="{{ get_setting('frontend_light_input_text', '#000000') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Light Border') }}</label>
                              <input type="hidden" name="types[]" value="frontend_light_border">
                              <input type="color" name="frontend_light_border" class="form-control" value="{{ get_setting('frontend_light_border', '#E92B58') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Dark Background') }}</label>
                              <input type="hidden" name="types[]" value="frontend_dark_background">
                              <input type="color" name="frontend_dark_background" class="form-control" value="{{ get_setting('frontend_dark_background', '#121316') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Dark Surface') }}</label>
                              <input type="hidden" name="types[]" value="frontend_dark_surface">
                              <input type="color" name="frontend_dark_surface" class="form-control" value="{{ get_setting('frontend_dark_surface', '#1C1D21') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Dark Surface Alt') }}</label>
                              <input type="hidden" name="types[]" value="frontend_dark_surface_alt">
                              <input type="color" name="frontend_dark_surface_alt" class="form-control" value="{{ get_setting('frontend_dark_surface_alt', '#26272C') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Dark Text Primary') }}</label>
                              <input type="hidden" name="types[]" value="frontend_dark_text_primary">
                              <input type="color" name="frontend_dark_text_primary" class="form-control" value="{{ get_setting('frontend_dark_text_primary', '#FF7A9B') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Dark Text Secondary') }}</label>
                              <input type="hidden" name="types[]" value="frontend_dark_text_secondary">
                              <input type="color" name="frontend_dark_text_secondary" class="form-control" value="{{ get_setting('frontend_dark_text_secondary', '#D98FA6') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Dark Text Hint') }}</label>
                              <input type="hidden" name="types[]" value="frontend_dark_text_hint">
                              <input type="color" name="frontend_dark_text_hint" class="form-control" value="{{ get_setting('frontend_dark_text_hint', '#B07186') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Dark Input Text') }}</label>
                              <input type="hidden" name="types[]" value="frontend_dark_input_text">
                              <input type="color" name="frontend_dark_input_text" class="form-control" value="{{ get_setting('frontend_dark_input_text', '#F2F3F5') }}">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">{{ translate('Dark Border') }}</label>
                              <input type="hidden" name="types[]" value="frontend_dark_border">
                              <input type="color" name="frontend_dark_border" class="form-control" value="{{ get_setting('frontend_dark_border', '#33353B') }}">
                          </div>
                      </div>
                  </div>
              </div>
              <div class="form-group row">
                  <label class="col-md-3 col-from-label">{{ translate('Member Public Profile Page Banner') }}</label>
                  <div class="col-md-4">
                      <div class="input-group " data-toggle="aizuploader" data-type="image">
                        <div class="input-group-prepend">
                          <div class="input-group-text bg-soft-secondary">{{ translate('Browse') }}</div>
                        </div>
                        <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                          <input type="hidden" name="types[]" value="public_profile_page_banner">
                          <input type="hidden" name="public_profile_page_banner" value="{{ get_setting('public_profile_page_banner') }}" class="selected-files">
                      </div>
                      <div class="file-preview box"></div>
                      <small class="text-muted">{{ translate('Banner Size- 450x650 .png') }}</small>
                  </div>
                  <div class="col-md-4">
                    <input type="hidden" name="types[]" value="public_profile_page_banner_link">
                    <input type="text" name="public_profile_page_banner_link" class="form-control" placeholder="{{ translate('link') }}" value="{{  get_setting('public_profile_page_banner_link') }}">
                  </div>
              </div>
              <div class="text-right">
                <button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
              </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h6 class="fw-600 mb-0">{{ translate('Global SEO') }}</h6>
        </div>
        <div class="card-body">
          <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group row">
              <label class="col-md-3 col-from-label">{{ translate('Meta Title') }}</label>
                <div class="col-md-8">
                  <input type="hidden" name="types[]" value="meta_title">
                  <input type="text" class="form-control" placeholder="Title" name="meta_title" value="{{ get_setting('meta_title') }}">
                </div>
            </div>
            <div class="form-group row">
              <label class="col-md-3 col-from-label">{{ translate('Meta description') }}</label>
                <div class="col-md-8">
                  <input type="hidden" name="types[]" value="meta_description">
                  <textarea class="resize-off form-control" placeholder="Description" name="meta_description">{{  get_setting('meta_description') }}</textarea>
                </div>
            </div>
            <div class="form-group row">
              <label class="col-md-3 col-from-label">{{ translate('Keywords') }}</label>
                <div class="col-md-8">
                  <input type="hidden" name="types[]" value="meta_keywords">
                  <textarea class="resize-off form-control" placeholder="Keyword, Keyword" name="meta_keywords">{{ get_setting('meta_keywords') }}</textarea>
                  <small class="text-muted">{{ translate('Separate with coma') }}</small>
                </div>
            </div>
            <div class="form-group row">
              <label class="col-md-3 col-from-label">{{ translate('Meta Image') }}</label>
                <div class="col-md-8">
                  <div class="input-group " data-toggle="aizuploader" data-type="image">
                    <div class="input-group-prepend">
                      <div class="input-group-text bg-soft-secondary">{{ translate('Browse') }}</div>
                    </div>
                    <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                    <input type="hidden" name="types[]" value="meta_image">
                    <input type="hidden" name="meta_image" value="{{ get_setting('meta_image') }}" class="selected-files">
                  </div>
                  <div class="file-preview box"></div>
                </div>
            </div>
            <div class="text-right">
              <button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h6 class="fw-600 mb-0">{{ translate('Cookies Agreement') }}</h6>
        </div>
        <div class="card-body">
          <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group row">
              <label class="col-md-3 col-from-label">{{ translate('Cookies Agreement Text') }}</label>
                <div class="col-md-8">
                  <input type="hidden" name="types[]" value="cookies_agreement_text">
                  <textarea name="cookies_agreement_text" rows="4" class="aiz-text-editor form-control" data-buttons='[["font", ["bold"]],["insert", ["link"]]]'>{{ get_setting('cookies_agreement_text') }}</textarea>
                </div>
            </div>
            <div class="form-group row">
              <label class="col-md-3 col-from-label">{{translate('Show Cookies Agreement?')}}</label>
              <div class="col-md-8">
                <label class="aiz-switch aiz-switch-success mb-0">
                  <input type="hidden" name="types[]" value="show_cookies_agreement">
                  <input type="checkbox" name="show_cookies_agreement" @if( get_setting('show_cookies_agreement') == 'on') checked @endif>
                  <span></span>
                </label>
              </div>
            </div>
            <div class="text-right">
              <button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
            </div>
          </form>
        </div>
      </div>

        <div class="card">
            <div class="card-header">
                <h6 class="fw-600 mb-0">{{ translate('Custom Script') }}</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group row">
                        <label class="col-md-3 col-from-label">{{ translate('Header custom script - before </head>') }}</label>
                        <div class="col-md-8">
                            <input type="hidden" name="types[]" value="header_script">
                            <textarea name="header_script" rows="4" class="form-control" placeholder="<script>&#10;...&#10;</script>">{{ get_setting('header_script') }}</textarea>
                            <small>{{ translate('Write script with <script> tag') }}</small>
                        </div>
                    </div>
                    @csrf
                    <div class="form-group row">
                        <label class="col-md-3 col-from-label">{{ translate('Footer custom script - before </body>') }}</label>
                        <div class="col-md-8">
                            <input type="hidden" name="types[]" value="footer_script">
                            <textarea name="footer_script" rows="4" class="form-control" placeholder="<script>&#10;...&#10;</script>">{{ get_setting('footer_script') }}</textarea>
                            <small>{{ translate('Write script with <script> tag') }}</small>
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
