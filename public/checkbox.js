$(document).ready(function () {

    $('#check-all').on('change', function () {
        $('.product-checkbox').prop('checked', $(this).prop('checked'));
        toggleDeleteButton();
    });


    $('.product-checkbox').on('change', function () {
        $('#check-all').prop('checked', $('.product-checkbox:checked').length === $('.product-checkbox').length);
        toggleDeleteButton();
    });


    function toggleDeleteButton() {
        if ($('.product-checkbox:checked').length > 0) {
            $('#delete-selected-container').fadeIn();
        } else {
            $('#delete-selected-container').fadeOut();
        }
    }

    $('#btn-delete-selected').on('click', function() {

        var model = $(this).data('model');
        var ids = $('.product-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if(ids.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Chưa chọn mục nào',
                text: 'Vui lòng chọn ít nhất 1 mục để xóa',
            });
            return;
        }
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        Swal.fire({
            title: 'Bạn có chắc chắn?',
            text: "Các mục đã chọn sẽ bị xóa vĩnh viễn!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/delete-multiple',
                    method: 'POST',
                    data: {
                        model: model,
                        ids: ids
                    },
                    success: function(res) {
                        if(res.success) {
                            $('#delete-selected-container').hide();
                            Swal.fire(
                                'Đã xóa!',
                                'Các mục đã chọn đã được xóa.',
                                'success'
                            );
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            Swal.fire(
                                'Lỗi!',
                                res.message || 'Xóa thất bại.',
                                'error'
                            )
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Lỗi!',
                            'Có lỗi xảy ra khi kết nối máy chủ.',
                            'error'
                        )
                    }
                });
            }
        });
    });


});
