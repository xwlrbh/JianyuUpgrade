/*** Created by A.J on 2019/3/7.*/$(document).ready(function(){var jlos = jLos.open(30, 5000);if(jlos.isValid && $("#jianyuluntan").length > 0){var pos = jlos.get($("#jianyuluntanscrollpos").text());if(pos != ""){$("#jianyuluntansidebar").scrollTop(pos);}$("#jianyuluntansidebar").on("mousedown", "a", function(){jlos.set($("#jianyuluntanscrollpos").text(), $("#jianyuluntansidebar").scrollTop());});}if($("#jianyuluntansidebar").hasClass("overflow-hidden")){$("#jianyuluntansidebar").removeClass("overflow-hidden").addClass("overflow-auto");}$("form").find("button.submit").css({"position": "relative","z-index": 1200}).on("click", function(){var subobj = $(this);subobj.children("i").removeClass("d-none");$.post("", subobj.parents("form").serialize(),function(data){subobj.children("i").addClass("d-none");if(data == "ok"){if($("#jumpto").length > 0 && $("#jumpto").text() != ""){window.location.href = $("#jumpto").text();}else if(!subobj.hasClass("notrefreshing")){window.location.reload();}}else{$.alert({title: $('#chucuo').text(),content: data,buttons: {confirm: {text: $('#queding').text(),btnClass: 'btn-info',keys: ['enter']}}});}});});$(".hasimg").find("img").addClass("img-fluid");if($("#remind").text() == 1){$.post("remind", { verification:$("#verification").text()},function(data){if(data == 0){$("#jianyuprompt").html("&#24863;&#35874;&#20351;&#29992;&#21073;&#40060;&#35770;&#22363;&#31995;&#32479;&#65292;&#24744;&#24403;&#21069;&#20351;&#29992;&#30340;&#26159;&#26410;&#25480;&#26435;&#29256;&#26412;&#65292;&#35831;&#35775;&#38382;<a href=\"http:\/\/www.jianyuluntan.com\" target=\"_blank\">&#21073;&#40060;&#35770;&#22363;&#23448;&#32593;<\/a>&#30003;&#35831;&#25480;&#26435;");}});}if($('#bbn').length > 0){$.post("bbn", {verification:$("#verification").text()});}$("form").find(".dropdown-item").on("click", function(){if(!$(this).prop("disabled")){$(this).parent().prev().text($.trim($(this).text()));$(this).parent().parent().next().val($(this).data("val"));}});$(".fa.fa-minus-square-o,.fa.fa-plus-square-o").css("cursor","pointer").on("click", function(){if($(this).hasClass("fa-minus-square-o")){$(this).removeClass("fa-minus-square-o").addClass("fa-plus-square-o");}else{$(this).removeClass("fa-plus-square-o").addClass("fa-minus-square-o");}$(this).parent().next("div").slideToggle();});$("#jianyubtn").on("click", function(){if($("#sidebar").hasClass("d-none")){$("#sidebar").removeClass("d-none").hide();}if($('#sidebarModal').hasClass("show")){$('#sidebarModal').modal('hide');$("#jianyunav").removeClass("zindex1070");$("#sidebar").removeClass("zindex1060");}else{$('#sidebarModal').modal('show');window.scrollTo(0,0);$("#jianyunav").addClass("zindex1070");$("#sidebar").addClass("zindex1060");}$("#sidebar").slideToggle();});$("#sidebarModal").on("click", function(){$("#jianyunav").removeClass("zindex1070");$("#sidebar").removeClass("zindex1060").fadeOut();});$("#hideshowmenu").on("click", function(){if($("#sidebar").hasClass("d-lg-block")){$("#sidebar").removeClass("d-lg-block");$(this).find("i").removeClass("fa-chevron-circle-left").addClass("fa-chevron-circle-right");}else{$("#sidebar").addClass("d-lg-block");$(this).find("i").removeClass("fa-chevron-circle-right").addClass("fa-chevron-circle-left");}});$("nav.flex-column a").hover(function(){$(this).addClass("overbg");},function(){$(this).removeClass("overbg");});$('[data-toggle="tooltip"]').tooltip();$("input").focusin(function(){$(this).css("background-color","#f1f7f8");});$("input").focusout(function(){$(this).css("background-color","#FFFFFF");});});