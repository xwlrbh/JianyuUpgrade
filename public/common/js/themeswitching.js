/*** Created by A.J on 2021/1/28.*/$(document).ready(function(){$('#upfile').on('change',function(){var fileName = $(this).val();$(this).next('.custom-file-label').html(fileName);});$('#formsubmit').on('click',function(){var subobj = $(this);subobj.attr("disabled",true).children("i").removeClass("d-none");$('#upfile').upload("uploadtheme", $("form#upreform").serialize(), function(data) {subobj.attr("disabled",false).children("i").addClass("d-none");if(data == "ok"){window.location.reload();}else{$.alert({title: $('#chucuo').text(),content: data,buttons: {confirm: {text: $('#queding').text(),btnClass: 'btn-info',keys: ['enter']}}});}});});});