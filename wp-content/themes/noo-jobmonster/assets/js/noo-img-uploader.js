jQuery(document).ready(function ($) {
    "use strict";
    var NOO_AIIU_Upload = {
        init:function () {
            window.nooImgUploadCount = $('#uploaded-images .uploaded-img').length;
            this.maxFiles = parseInt(noo_img_upload.max_files);

            $('#uploaded-images').on('click', 'a.remove-img', this.removeUploads);

            this.attach();
            this.hideUploader();
        },
        attach:function () {
            // wordpress plupload if not found
            if (typeof(plupload) === 'undefined') {
                return;
            }
            if( !$('#aaiu-uploader').length ) {
            	return;
            }

            if( !$('#cover-upload').length ) {
                return;
            }
            var uploader = new plupload.Uploader(noo_img_upload.plupload);

            $('#aaiu-uploader').click(function (e) {
                uploader.start();
                // To prevent default behavior of a tag
                e.preventDefault();
            });

            $('#cover-upload').click(function (e) {
                uploader.start();
                // To prevent default behavior of a tag
                e.preventDefault();
            });

            //initilize  wp plupload
            uploader.init();

            uploader.bind('FilesAdded', function (up, files) {
                $.each(files, function (i, file) {
                    $('#aaiu-upload-imagelist').append(
                        '<div id="' + file.id + '">' +
                            file.name + ' (' + plupload.formatSize(file.size) + ') <b></b>' +
                            '</div>');
                });

                up.refresh(); // Reposition Flash/Silverlight
                uploader.start();
            });

            uploader.bind('UploadProgress', function (up, file) {
                $('#' + file.id + " b").html(file.percent + "%");
            });

            // On erro occur
            uploader.bind('Error', function (up, err) {
                $('#aaiu-upload-imagelist').append("<div>Error: " + err.code +
                    ", Message: " + err.message +
                    (err.file ? ", File: " + err.file.name : "") +
                    "</div>"
                );

                up.refresh(); // Reposition Flash/Silverlight
            });

            uploader.bind('FileUploaded', function (up, file, response) {
                var result = $.parseJSON(response.response);
                $('#' + file.id).remove();
                if (result.success) {
                    window.nooImgUploadCount += 1;

                    if( $('#company_logo').length > 0 ) {
                        $('#company_logo').val(result.image_id);
                        $('#uploaded-images')
                            .empty()
                            .css('margin-bottom','10px')
                            .append('<div class="uploaded-img" data-imageid="'+result.image_id+'"><img style="max-height: 80px; max-width: 80px;" src="'+result.thumbnail+'" /></div>');

                        $('.user-sidebar-menu .user-avatar > img').attr('src', result.image);
                        $('.nav-item-member-profile .profile-avatar > img').attr('src', result.image);
                    }else if( $('#cover_image').length > 0 ) {
                        $('#cover_image').val(result.image_id);
                        $('#uploaded-images')
                            .empty()
                            .css('margin-bottom','10px')
                            .append('<div class="uploaded-img" data-imageid="'+result.image_id+'"><img style="max-height: 80px; max-width: 80px;" src="'+result.thumbnail+'" /></div>');

                        $('.user-sidebar-menu .user-avatar > img').attr('src', result.image);
                        $('.nav-item-member-profile .profile-avatar > img').attr('src', result.image);
                    }else if( $('#profile_image').length > 0 ) {
                        $('#profile_image').val(result.image_id);
                        $('#uploaded-images')
                            .empty()
                            .css('margin-bottom','10px')
                            .append('<div class="uploaded-img" data-imageid="'+result.image_id+'"><img style="max-height: 80px; max-width: 80px;" src="'+result.thumbnail+'" /></div>');

                        $('.nav-item-member-profile .profile-avatar > img').attr('src', result.image);
                    }else{
                    	 $('#uploaded-images').empty().css('margin-bottom','10px')
                         .append('<div class="uploaded-img"></div>');

                    }

                    NOO_AIIU_Upload.hideUploader();
                }
            });


        },

        hideUploader:function () {

            if (NOO_AIIU_Upload.maxFiles !== 0 && window.nooImgUploadCount >= NOO_AIIU_Upload.maxFiles) {
                $('#aaiu-uploader').hide();
                $('#cover-upload').hide();
            }
        },

        removeUploads:function (e) {
            e.preventDefault();

            if (confirm(noo_img_upload.confirmMsg)) {

                var el = $(this),
                    data = {
                        'attach_id':el.parent().attr('data-imageid'),
                        'nonce':noo_img_upload.remove,
                        'action':'noo_delete_file'
                    };

                $.post(noo_img_upload.ajaxurl, data, function (response) {
                    el.parent().remove();

                    window.nooImgUploadCount -= 1;
                    if (NOO_AIIU_Upload.maxFiles !== 0 && window.nooImgUploadCount < NOO_AIIU_Upload.maxFiles) {
                        $('#aaiu-uploader').show();
                        $('#cover-upload').show();
                    }
                });
            }
        }
    };
    NOO_AIIU_Upload.init();
});