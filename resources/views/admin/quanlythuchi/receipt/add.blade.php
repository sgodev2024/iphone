@extends('admin.layout.index')

@section('content')


<div class="page-inner">
    <div class="page-header">
        <x-breadcrumb :items="[['label' => 'Quản lý thu','url' => route('admin.quanlythuchi.expense.index')],['label' => 'Thêm']]" />
        {{-- <ul class="breadcrumbs mb-3">
            <li class="nav-home">
                <a href="{{ route('admin.dashboard') }}">
                    <i class="icon-home"></i>
                </a>
            </li>
            <li class="separator">
                <i class="icon-arrow-right"></i>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.quanlythuchi.expense.index') }}">Quản lý thu</a>
            </li>
            <li class="separator">
                <i class="icon-arrow-right"></i>
            </li>
            <li class="nav-item">
                <a href="#">Thêm</a>
            </li>
        </ul> --}}
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title" style="text-align: center; color:#fff">Thêm phiếu thu</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.quanlythuchi.receipts.addSubmit') }}" method="post" id="addreceipt" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="content">Khách hàng:</label>
                                    <select class="form-control" name="client" id="client">
                                        <option value="">--- Chọn khách hàng---</option>
                                        @foreach ($debtClient as $item)
                                        <option value="{{ $item->client_id }}">{{ $item->client->name .' ( '. $item->client->phone .' ) ' }}</option>
                                        @endforeach
                                    </select>
                                    <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                                        id="client_error"></span> </div>
                                </div>

                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount_spent">Số tiền :</label>
                                    <input type="number" class="form-control" min="1000" max="" value="" id="amount_spent" name="amount_spent" required>
                                    <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                                        id="amount_spent_error"></span> </div>
                                </div>

                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="content">Nội dung:</label>
                                    <input type="text" class="form-control" id="content" name="content" >
                                    <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                                        id="content_error"></span> </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="button" class="btn btn-primary" onclick="addreceipts(event)">Lưu</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function(){
        $("#client").change(function(){
            var client = $(this).val();
            if(client > 0){
                $.ajax({
                    url: '{{ route('admin.quanlythuchi.receipts.debt') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        client: client,
                    },
                    success: function(data) {
                        $('#amount_spent').val(parseInt(data.replace(",", ""), 10));
                        $('#amount_spent').attr('max', parseInt(data.replace(",", ""), 10));
                    },
                });
            }else{
                $('#amount_spent').val('');
            }

        })
    })
</script>
<script>
    var validateorder = {
        'client': {
            'element': document.getElementById('client'),
            'error': document.getElementById('client_error'),
            'validations': [
                {
                    'func': function(value){
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('TC003')
                },
            ]
        },
        'amount_spent': {
            'element': document.getElementById('amount_spent'),
            'error': document.getElementById('amount_spent_error'),
            'validations': [
                {
                    'func': function(value){
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('TC002')
                },
            ]
        },
        'content': {
            'element': document.getElementById('content'),
            'error': document.getElementById('content_error'),
            'validations': [
                {
                    'func': function(value){
                        return checkRequired(value);
                    },
                    'message': generateErrorMessage('TC004')
                },
            ]
        },
    }
    function addreceipts(event){
        event.preventDefault();
        if(validateAllFields(validateorder)){
            document.getElementById('addreceipt').submit();
        }
    }
</script>
@endsection
