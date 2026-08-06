
/* global fcom */
$(document).ready(function () {
    profileInfoForm();
});
(function () {
    var dv = '#profileInfoFrmBlock';
    profileInfoForm = function () {
        fcom.ajax(fcom.makeUrl('Profile', 'profileInfoForm'), '', function (t) {
            $(dv).html(t);
        });
    };
    updateProfileInfo = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Profile', 'updateProfileInfo'), fcom.frmData(frm), function (t) { });
    };
    removeProfileImage = function () {
        if(!confirm(langLbl.confirmRemove)){
            return false;
        }
        fcom.ajax(fcom.makeUrl('Profile', 'removeProfileImage'), '', function (t) {
            profileInfoForm();
        });
    };
    sumbmitProfileImage = function () {
        if (cropObj) {
            /* Add blob and file name */
            $image.cropper('getCroppedCanvas').toBlob(function (blob) {
                $.loader.hide();
                var formData = new FormData();
                formData.append('user_profile_image', blob, 'file.jpg');
                formData.append('fIsAjax', 1);
                fcom.ajaxMultipart(fcom.makeUrl('Profile', 'uploadProfileImage'), formData, function (res) {
                    $.loader.hide();
                    $image.cropper('destroy');
                    $.w3mentorsmodal.close();
                    profileInfoForm();
                }, {fOutMode: 'json'});
            }, 'image/jpeg', 0.9);
        }
    };
    $(document).on('click', '[data-method]', function () {
        var data = $(this).data();
        if (data.method) {
            result = $image.cropper(data.method, data.option);
        }
    });
    var $image = null;
    var cropObj = null;
    cropImage = function (obj) {
        if ($image) {
            $image.cropper('destroy');
        }
        $image = obj;
        cropObj = $image.cropper({
            aspectRatio: 1,
            guides: false,
            highlight: false,
            dragCrop: false,
            cropBoxMovable: false,
            cropBoxResizable: false,
            rotatable: true,
            responsive: true,
            built: function () {
                $(this).cropper("zoom", 0.5);
            },
        })
    };
    popupImage = function (input) {
        if (input.value == '') {
            return;
        }
        if (input.files && input.files[0]) {
            var allowedExtensions = /(\.jpg|\.jpeg|\.png|\.gif)$/i;
            if (!allowedExtensions.exec(input.value)) {
                input.value = '';
                fcom.error(langLbl.invalidExtension);
                return false;
            }
            var reader = new FileReader();
            reader.onload = function (e) {
                $.w3mentorsmodal('<div class="p-4"><div class="img-container"><img alt="Picture" src="' + e.target.result + '" class="" id="new-img" /></div><span class="gap"></span><div class="d-flex flex-wrap gap-2 g-4 justify-content-center"><a href="javascript:void(0)" class="btn btn-primary btn-sm" title="' + $("#rotate_left").val() + '" data-option="-90" data-method="rotate">' + $("#rotate_left").val() + '</a>&nbsp;<a onclick="sumbmitProfileImage();" href="javascript:void(0)" class="btn btn-primary btn-sm">' + $("#update_profile_img").val() + '</a>&nbsp;<a href="javascript:void(0)" class="btn btn-primary btn-sm rotate-right" title="' + $("#rotate_right").val() + '" data-option="90" data-method="rotate" type="button">' + $("#rotate_right").val() + '</a></div></div>', true, 'modal-lg');
                setTimeout(function () {
                    cropImage($('#new-img'));
                }, 300);
            };
            reader.readAsDataURL(input.files[0]);
        }
    };
})();