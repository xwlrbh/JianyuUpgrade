/*** Created by A.J on 2021/5/24.*/$(document).ready(function(){if($('#tupian').val() != ""){pic = $("#domain").text()+$('#tupian').val();$('#tupianImg').attr("src", pic);}var pic='';$('#upload').uploadify({auto:true,fileTypeExts:'*.jpg;*.png;*.gif;*.jpeg',multi:false,fileSizeLimit:9999,buttonText:$('#buttonText').text(),showUploadedPercent:true,showUploadedSize:false,removeTimeout:3,uploader:'uploadslide',onUploadStart:function(){this.formData = {gid:$("#gid").val(),upd:$("#tupian").val(),verification:$("#verification").text()};},onUploadComplete:function(file,data){pic = $("#domain").text()+data;$('#tupian').val(data);$('#tupianImg').attr("src", pic);}});});