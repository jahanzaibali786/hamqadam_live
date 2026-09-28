<div class="modal-header">
    <h5 class="modal-title h6">{{translate('Payment Details')}}</h5>
    <button type="button" class="close" data-dismiss="modal"></button>
</div>
<div class="modal-body">
  <table class="table table-bordered">
        <tbody>
            <tr>
                <th>{{translate('Payment Method')}}</th>
                <td>{{ $package_payment->custom_payment_name }}</td>
            </tr>
            <tr>
                <th>{{translate('Transaction Id')}}</th>
                <td>{{ $package_payment->custom_payment_transaction_id }}</td>
            </tr>
            <tr>
                <th>{{translate('Payemnt Proof')}}</th>
                <td>
                    <a href="{{ uploaded_asset($package_payment->custom_payment_proof) }}" target="_blank" download="">
                        <span>{{ translate('Download') }}</span>
                    </a>
                </td>
            </tr>
            <tr>
                <th>{{translate('Details')}}</th>
                <td>{{ $package_payment->custom_payment_details }}</td>
            </tr>
        </tbody>
  </table>
</div>
<div class="modal-footer">
    @if($package_payment->payment_status != 'Paid' && $package_payment->payment_status != 'Rejected')
      <a href="{{ route('manual_payment_accept', $package_payment->id) }}"
         onclick="return confirm('{{ translate('Approve this payment and activate the package?') }}')"
         class="btn btn-sm btn-success">{{translate('Approve & Activate')}}</a>
      <a href="{{ route('manual_payment_reject', $package_payment->id) }}"
         onclick="return confirm('{{ translate('Reject this payment request?') }}')"
         class="btn btn-sm btn-danger">{{translate('Reject')}}</a>
    @elseif($package_payment->payment_status == 'Rejected')
      <span class="badge badge-inline badge-danger">{{ translate('Rejected') }}</span>
    @endif
    <button type="button" class="btn btn-sm btn-light" data-dismiss="modal">{{translate('Close')}}</button>
</div>
