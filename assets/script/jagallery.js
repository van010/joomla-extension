var current_jagallery = options.curr_gallery;
var current_jafolder = options.curr_folder;
var autoUpdate = options.auto_update;
const gallery_id = jQuery(`#${options.gallery_id}`);

jQuery(document).ready(function () {

    // init modal
    init_modal();

    jQuery("#jaGetImages").click(function () {
        jaListImages();
    });
    jaListImages();
    if (autoUpdate) {
        Joomla.submitbutton("module.apply");
    }
});

function jaListImages() {
    var folder_path = jQuery("#jform_params_folder").val();
    if (folder_path == "") {
        alert(options.folder_require);
        return;
    }
    const orderby = jQuery("#jform_params_source_images_orderby").val();
    const sortby = jQuery("#jform_params_source_images_sort").val();
    const query = "jarequest=images&path=" + folder_path + "&jatask=loadImages&orderby=" + orderby + "&sortby=" + sortby;

    jQuery.ajax({
        url: location.href,
        data: query,
        type: "post",
        beforeSend: function () {
            jQuery("#listImages").html(`<img src="${options.path_gif_loading}" width="30" height="30"/>`);
        },
        success: function (responseJSON) {
            var data = jQuery.parseJSON(responseJSON);
            if (!data.success) {
                jQuery(`#${options.gallery_id}`).val("");
                jQuery("#listImages").html(`<strong style=\'color: red\'>${options.folder_empty}</strong>`);
                return;
            }
            else {
                jaupdateImages(data.images, "#listImages");
                if (folder_path === current_jafolder && current_jagallery != "") {
                    var current_data = jQuery.parseJSON(current_jagallery);
                    for (var i = 0; i < current_data.length; i++) {
                        for (var j = 0; j < data.images.length; j++) {
                            if (current_data[i].image == data.images[j].image) {
                                if (typeof (current_data[i].title) !== "undefined") {
                                    data.images[j].title = current_data[i].title;
                                }
                                if (typeof (current_data[i].link) !== "undefined") {
                                    data.images[j].link = current_data[i].link;
                                }
                                if (typeof (current_data[i].description) !== "undefined") {
                                    data.images[j].description = current_data[i].description;
                                }
                                if (typeof (current_data[i].show) !== "undefined") {
                                    data.images[j].show = current_data[i].show;
                                }
                                break;
                            }
                        }
                    }
                }
                jaupdateImages(data.images, "#listImages");
            }

        }
    });

    return false;
}

function jaupdateImages(images, boxID) {
    var data = "";
    if (images.length) {
        for (var i = 0; i < images.length; i++) {
            var showImage = "";
            if (images[i].show === true || images[i].show === "true") {
                showImage = "checked";
            }
            data += "<div class=\'img-element\' style=\'width: 100px; height: 150px; float: left; margin: 0 5px;\'>";
            data += "<img src=\'" + encodeURI(images[i].imageSrc) + "\' style=\'max-width: 100px; max-height: 100px;\' />";
            data += "<br />";
            data += "<span style=\'float: left; display: block; text-align: center\'>";
            data += `${options.text_show}<input style=\'margin:0 auto;\' type=\'checkbox\' value=\'` + images[i].image + "\' " + showImage + " onchange=\'showImage(this)\' />";
            data += "</span>";
            data += "<span onclick=\'jaFormpopup(\"#img-element-data-form\", " + i + ", \"" + images[i].image + "\");";
            data += `return false;\' class=\'img-btn\' style=\'float: right; text-align: center; display: block; cursor: pointer;\'><b>${options.text_edit}</b></span>`;
            data += "</div>";
        }
        data += "<div id=\'img-element-data-form\' style=\'display: none;\'></div>";
    }
    jQuery(boxID).html(data);
    jQuery(`#${options.gallery_id}`).val(JSON.stringify(images));
}

function showImage(el) {
    const showImage = jQuery(el).is(':checked');
    const gallery_id = jQuery(`#${options.gallery_id}`);
    const data = jQuery.parseJSON(gallery_id.val());

    if (!data) {
        data = [];
    }
    if (data.length > 0) {
        for (var i = 0; i < data.length; i++) {
            if (data[i]["image"] == jQuery(el).val()) {
                data[i]["show"] = showImage;
                break;
            }
        }
    }
    gallery_id.val(JSON.stringify(data));
}

function jaFormpopup(el, key, imgname) {
    var form = jadataForm(key, imgname);
    jQuery(el).append(form);
    const curr_data_form = $(`#img-element-data-form-${key}`);
    
    
    modal_handling(curr_data_form);

    //update data for image form
    const data = jQuery(`#${options.gallery_id}`).val();
    const jaimg = new Object();
    jaimg.title = "";
    jaimg.link = "";
    jaimg.description = "";
    //query = "jarequest=images&task=validData&imgname="+imgname+"&data="+data;
    jQuery.ajax({
        url: location.href,
        data: { jarequest: "images", jatask: "validData", imgname: imgname, data: data },
        type: "post",
        success: function (responseJSON) {
            var jaResponse = jQuery.parseJSON(responseJSON);
            jQuery("#img-element-data-form-" + key).find("#imgtitle").val(jaResponse.title);
            jQuery("#img-element-data-form-" + key).find("#imglink").val(jaResponse.link);
            jQuery("#img-element-data-form-" + key).find("#imgdescription").val(jaResponse.description);
        }
    });
}

function modal_handling(curr_data_form){
    const over_lay = $('div#ja-modal-overlay');
    open_modal(over_lay, curr_data_form);
    modal_content_handling(curr_data_form);
    // detect window click
    // $(window).on('click', destroy_modal_overlay);
    $(over_lay).on('click', destroy_modal_overlay);
    esc_close_modal();
}

function modal_content_handling(img_data_form){
    const box_window = $('div#ja-modal-window');
    if (box_window.children().length > 0){
        box_window.empty();
    }
    img_data_form.appendTo(box_window);
}

function open_modal(form, curr_data_form){
    (function ($){
        const over_lay = $('div#ja-modal-overlay');
        const box_window = $('div#ja-modal-window');
        const window_size = {
            width: window.innerWidth,
            height: window.innerHeight
        };

        box_window.attr('aria-hidden', false);
        over_lay.attr('aria-hidden', false);
        
        box_window.css({display: 'block'});
        over_lay.css({display: 'block'});

        over_lay.animate({
            opacity: .7,
            width: `${window_size.width}px`,
            height: `${window_size.height}px`,
        }, 400, 'easeInCubic');

        box_window.animate({
            opacity: 1,
        }, 600, 'easeInCubic');

        // stop scroll when overlay popup
        $('body', 'html').css({'overflow': 'hidden'});

    })(jQuery)
}

function destroy_modal_overlay(){
    (function ($){
        const over_lay = $('div#ja-modal-overlay');
        const box_window = $('div#ja-modal-window');
        
        over_lay.attr('aria-hidden', true);
        box_window.attr('aria-hidden', true);

        over_lay.fadeOut(400, 'easeOutQuart', function(){
            over_lay.css({
                opacity: 0,
                'z-index': 65555,
                display: 'none'
            });
        })
        box_window.fadeOut(400, 'easeOutQuart', function (){
            box_window.css({
                opacity: 0,
                'z-index': 65555,
                display: 'none'
            })
        })

        // start scroll when overlay destroy
        $('body', 'html').css({'overflow': 'auto'});
    })(jQuery)
}

function esc_close_modal(){
    document.addEventListener('keydown', function (event){
        if (event.key.toLocaleLowerCase() === 'escape' || event.key === 27){
            destroy_modal_overlay();
        }
    })
}


function init_modal(){
    (function ($){
        $('<div>', {
            id: 'ja-modal-overlay',
            'aria-hidden': true,
            tabindex: -1,
            style: "z-index: 65555, opacity: 0;"
                
        }).appendTo($('body'));
        $('<div>', {
            id: 'ja-modal-window',
            class: 'shadow',
            role: 'window',
            'aria-hidden': true,
            style: 'z-index:'
        }).appendTo($('body'));


    })(jQuery)
}

function jaCloseImgForm(key) {
    destroy_modal_overlay();
}

function jaUpdateImgData(key, imgname) {
    const img_data_form = $(`#img-element-data-form-${key}`);
    const title = img_data_form.find("#imgtitle").val();
    const link = img_data_form.find("#imglink").val();
    const description = img_data_form.find("#imgdescription").val();
    const data = jQuery.parseJSON(jQuery(`#${options.gallery_id}`).val());

    if (!data) { data = []; }

    if (data.length > 0) {
        var found = false;

        for (var i = 0; i < data.length; i++) {
            if (data[i]["image"] == imgname) {
                data[i]["title"] = title;
                data[i]["link"] = link;
                data[i]["description"] = description;

                found = true;
                break;
            }
        }

        if (!found) {
            data_add = new Object();
            data_add["image"] = imgname;
            data_add["title"] = title;
            data_add["link"] = link;
            data_add["description"] = description;
            data.push(data_add);
        }
    } else {
        data_add = new Object();
        data_add["image"] = imgname;
        data_add["title"] = title;
        data_add["link"] = link;
        data_add["description"] = description;
        data.push(data_add);
    }

    jQuery(`#${options.gallery_id}`).val(JSON.stringify(data));
    alert(`Updated successfully: ${imgname}`);

    jaCloseImgForm(key);
}

function jadataForm(key, imgname) {

    //create form for image data
    var html = "";
    html += "<div id=\'img-element-data-form-" + key + "\' class=\'img-element-data-form\'>";
    html += "<fieldset class=\'panelform\' >";
    html += "<ul>";
    html += "<li>";
    html += `<label>Image</label>`;
    html += `<p><b>${imgname}</b></p>`;
    html += "</li>";
    html += "<li>";
    html += `<label>${options.text_title}</label>`;
    html += "<input type=\'text\' name=\'imgtitle\' id=\'imgtitle\' value=\'\' size=\'50\' />";
    html += "</li>";
    html += "<li>";
    html += `<label>${options.text_link}</label>`;
    html += "<input type=\'text\' name=\'imglink\' id=\'imglink\' value=\'\' size=\'50\' />";
    html += "</li>";
    html += "<li>";
    html += `<label>${options.text_desc}</label>`;
    html += "<textarea rows=\'6\' cols=\'80\' name=\'imgdescription\' id=\'imgdescription\' ></textarea>";
    html += "</li>";
    html += "</ul>";
    html += "<div class=\'btn-image-data-popup\' style=\'width: 100%; display: block; float: left; margin-top: 10px;\'>";
    html += "<input onclick=\'jaUpdateImgData(" + key + ", \"" + imgname + "\"); return false;\' type=\'button\' value=\'Update\' >";
    html += "<input onclick=\'jaCloseImgForm(" + key + "); return false;\' type=\'button\' value=\'Cancel\' >";
    html += "</div>";
    html += "</fieldset>";
    html += "</div>";

    return html;
}