@extends('admin.layouts.app')

@section('content')

    <div class="row">
        <div class="col-lg-6 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{translate('Edit City Info')}}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('cities.update', $city->id) }}" method="POST" >
                        <input name="_method" type="hidden" value="PATCH">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="name">{{translate('Country')}}</label>
                            <select class="form-control aiz-selectpicker" id="country_id" data-live-search="true" name="country_id" required>
                                @foreach($countries as $country)
                                    <option value="{{$country->id}}">{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="name">{{translate('State')}}</label>
                            <select class="form-control aiz-selectpicker" name="state_id"  data-live-search="true"  id="state_id"  required>

                            </select>
                            @error('state_id')
                                <small class="form-text text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="name">{{translate('City Name')}}</label>
                            <input type="text" id="name" name="name" value="{{$city->name}}" class="form-control"
                                   required>
                           @error('name')
                               <small class="form-text text-danger">{{ $message }}</small>
                           @enderror
                        </div>

                        <div class="form-group mb-3 text-right">
                            <button type="submit" class="btn btn-primary">{{translate('Update')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection



@section('script')
    <script type="text/javascript">
        function get_state_by_country(selected_state_id = null){
            var country_id = $('#country_id').val();
            $.post('{{ route('states.get_state_by_country') }}',{_token:'{{ csrf_token() }}', country_id:country_id}, function(data){
                var stateSelect = $('#state_id');
                stateSelect.html(null);
                stateSelect.append($('<option>', {
                    value: '',
                    text: '{{ translate('Select State') }}'
                }));
                for (var i = 0; i < data.length; i++) {
                    stateSelect.append($('<option>', {
                        value: data[i].id,
                        text: data[i].name,
                        selected: selected_state_id && String(selected_state_id) === String(data[i].id)
                    }));
                }
                stateSelect.selectpicker('refresh');
            });

        }

        $(document).ready(function(){
            $('#country_id').val('{{$city->state->country_id}}');
            get_state_by_country('{{$city->state_id}}');
        });

        $('#country_id').on('change changed.bs.select', function() {
            get_state_by_country();
        });

    </script>
@endsection
