/*** Created by A.J on 2019/10/4.*/$(document).ready(function(){if($("#tubiao").val() != ''){$('#tubiaoImg').attr("src", $("#tubiao").val());}var pic='';$('#upload').uploadify({auto:true,fileTypeExts:'*.jpg;*.png;*.gif;*.jpeg;*.webp',multi:false,formData:{verification:$("#verification").text()},fileSizeLimit:9999,buttonText:$('#buttonText').text(),showUploadedPercent:true,showUploadedSize:false,removeTimeout:3,uploader:'uploadimage',onUploadComplete:function(file,data){pic = $("#domain").text()+data;$('#tubiao').val(pic);$('#tubiaoImg').attr("src", pic);}});if($("#icotubiao").val() != ''){$('#icotubiaoIco').attr("src", $("#icotubiao").val());}var pic_ico='';$('#upload_ico').uploadify({auto:true,fileTypeExts:'*.ico',multi:false,formData:{verification:$("#verification").text()},fileSizeLimit:9999,buttonText:$('#icobuttonText').text(),showUploadedPercent:true,showUploadedSize:false,removeTimeout:3,uploader:'uploadIco',onUploadComplete:function(file,data){pic_ico = $("#domain").text()+data;$('#icotubiao').val(pic_ico);$('#icotubiaoIco').attr("src", pic_ico);}});$("form").submit(function(e){var ckdm = /^(http:\/\/|https:\/\/)[^ |,]+\/$/;if(!ckdm.test($("#domainid").val())){$.alert({title: $('#chucuo').text(),content: $("#yumingtishi").text(),confirmButton: $('#queding').text()});setTimeout(function(){$("#submitid").find("span:eq(0)").addClass("hidden");},1);return false;}});});