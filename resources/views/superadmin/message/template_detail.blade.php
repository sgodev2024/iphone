   <style>
       .table-responsive {
           margin-top: 20px;
       }

       .table-params {
           width: 100%;
       }

       .table-bordered {
           width: 100%;
           margin-bottom: 0;
       }

       .table-bordered th,
       .table-bordered td {
           padding: 8px;
           vertical-align: middle;
           border: 1px solid #dee2e6;
       }

       .modal-dialog {
           max-width: 90%;
           margin: 1.75rem auto;
       }

       .modal-content {
           border-radius: 0.5rem;
       }

       .toggle-link {
           cursor: pointer;
           color: blue;
           font-weight: bold;
           text-decoration: underline;
       }
   </style>

   @if (isset($responseData) && !empty($responseData))
       <div class="table-responsive">
           <table class="table table-bordered">
               <thead>
                   <tr>
                       <th>Thông tin</th>
                       <th>Giá trị</th>
                   </tr>
               </thead>
               <tbody>
                   <tr>
                       <td>Template ID</td>
                       <td>{{ $responseData['templateId'] ?? 'Không có dữ liệu' }}</td>
                   </tr>
                   <tr>
                       <td>Tên Template</td>
                       <td>{{ $responseData['templateName'] ?? 'Không có dữ liệu' }}</td>
                   </tr>
                   <tr>
                       <td>
                           <a class="toggle-link" data-toggle="modal" data-target="#paramsModal" role="button">
                               Danh sách tham số
                           </a>
                       </td>
                       <td>
                           {{ count($responseData['listParams'] ?? []) }}
                       </td>
                   </tr>
                   <tr>
                       <td>
                           Giá
                       </td>
                       <td>
                           {{ number_format($responseData['price'], 0) ?? 'Không có dữ liệu' }} đ/ZNS
                       </td>
                   </tr>
                   <tr>
                       <td>Trạng thái</td>
                       <td>
                           @switch($responseData['status'])
                               @case('PENDING_REVIEW')
                                   Chờ duyệt
                               @break

                               @case('DISBALE')
                                   Vô hiệu hóa
                               @break

                               @case('ENABLE')
                                   Đã kích hoạt
                               @break

                               @case('REJECT')
                                   Bị từ chối
                               @break

                               @default
                                   Không có dữ liệu
                           @endswitch
                       </td>
                   </tr>
                   <tr>
                       <td>Đường dẫn xem mẫu</td>
                       <td>
                           @if (isset($responseData['previewUrl']))
                               <a href="{{ $responseData['previewUrl'] }}" target="_blank"
                                   rel="noopener noreferrer">{{ $responseData['previewUrl'] }}</a>
                           @else
                               Không có dữ liệu
                           @endif
                       </td>
                   </tr>
               </tbody>
           </table>
       </div>
   @else
       <div class="alert alert-warning" role="alert">
           Không có dữ liệu hoặc lỗi khi lấy thông tin template.
       </div>
   @endif

   <!-- Modal -->
   <div class="modal fade" id="paramsModal" tabindex="-1" role="dialog" aria-labelledby="paramsModalLabel"
       aria-hidden="true">
       <div class="modal-dialog" role="document">
           <div class="modal-content">
               <div class="modal-header">
                   <h5 class="modal-title" id="paramsModalLabel">Danh sách tham số</h5>
                   <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                       <span aria-hidden="true">&times;</span>
                   </button>
               </div>
               <div class="modal-body">
                   <div class="table-responsive">
                       <table class="table table-sm table-striped table-params">
                           <thead>
                               <tr>
                                   <th>Tên tham số</th>
                                   <th>Mô tả</th>
                               </tr>
                           </thead>
                           <tbody>
                               @foreach ($responseData['listParams'] ?? [] as $param)
                                   <tr>
                                       <td>{{ $param['name'] }}</td>
                                       <td>
                                           @switch($param['name'])
                                               @case('name')
                                                   Tên của người nhận.
                                               @break

                                               @case('order_code')
                                                   Mã đơn hàng duy nhất.
                                               @break

                                               @case('phone_number')
                                                   Số điện thoại của người nhận.
                                               @break

                                               @case('status')
                                                   Trạng thái của đơn hàng.
                                               @break

                                               @case('date')
                                                   Ngày tháng liên quan đến giao dịch.
                                               @break

                                               @case('payment_status')
                                                   Phương thức thanh toán
                                               @break

                                               @case('customer_code')
                                                   Mã khách hàng
                                               @break

                                               @default
                                                   Không có mô tả.
                                           @endswitch
                                       </td>
                                   </tr>
                               @endforeach
                           </tbody>
                       </table>
                   </div>
               </div>
               <div class="modal-footer">
                   <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
               </div>
           </div>
       </div>
   </div>

   <!-- Include jQuery and Bootstrap JS in your layout file -->
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
