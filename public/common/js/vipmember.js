/*** Created by A.J on 2020/9/29.*/$(document).ready(function(){var gupobj;$(".xuqia").on("click", function(){gupobj = $(this);$('#vipmodal').modal('show');});$("#huiyuanqixianok").on("click", function(){$(this).children("i").removeClass("d-none");$.post("viprenewal", { id: gupobj.next().val(),leixing: $("#huiyuanleixing").val(),shixian: $("#huiyuanqixian").val(), verification:$("#verification").text()}, function(data){$("#huiyuanqixianok").children("i").addClass("d-none");if(data.result != 'ok'){$.alert({title: $('#chucuo').text(),content: data.message,buttons: {confirm: {text: $('#queding').text(),btnClass: 'btn-info',keys: ['enter']}}});}else{gupobj.parent().prev().text(data.viptype).prev().text(data.vipend).removeClass("text-secondary").addClass("text-success");$('#vipmodal').modal('hide');}});});$("#huiyuanleixing").change(function(){if($(this).val() == 3){$("#huiyuanqixiandiv").addClass("d-none");}else{$("#huiyuanqixiandiv").removeClass("d-none");}});});