var pdfUrl='';
var bindCalls=0;
var ajaxCalls=0;
	
function storeSortTable(tableName,tableHTML){
	if (typeof tableHTML === "undefined") {
		return;
	}
	  
	  bindCalls++;
	  if($('.icon-pdf').parent().attr('href')!="#")
	  {
		  pdfUrl=$('.icon-pdf').parent().attr('href');
		  $('.icon-pdf').parent().attr('href',"#");
		   
	  }
	  //calling ajax function to store tableHTML in case of pdf button is clicked
	  //this is the ajax action once the confirm submit button is clicked;
	  tableName=tableName.replace("table#","#");

	  tableHTML=tableHTML.replace("on<x>click=","");
	$.ajax({
		type: 'POST',
		url: 'tiki-ajax_services.php',
		dataType: 'json',
		data: {
				controller: 'pdf',
				action: 'storeTable',
				tableName:tableName,
				tableHTML:tableHTML
				
		},
		success: function (data) {
		$('#tikifeedback').hide();
	    	        ajaxCalls++;
				if(ajaxCalls==bindCalls)
                { 
				  window.location.href=pdfUrl;
			      $('.icon-pdf').parent().attr('href',pdfUrl);
				}
	
	       
		}
	});
	}
	var interval;
	function checkPDFFile(){
		$.ajax({
			type: 'POST',
			url: 'tiki-ajax_services.php',
			dataType: 'json',
			data: {
				controller: 'pdf',
				action: 'checkPDFFile',
			},
			success: function (data) {
				if(data!=false) {
					setTimeout(function() {$('.wikiinfo').html('');}, data);
					clearInterval(interval);
				}
			}
		});
	}
	
	//binding spinner call
	$('.icon-pdf').parent().bind( "click", function() {
		//show spinner
		$('.wikiinfo').html('<div class="alert alert-info   highlight" style="width:500px"><h4><span class="icon icon-information fa fa-info-circle fa-fw "></span>&nbsp;<span class="rboxtitle">Please wait</span></h4><div class="rboxcontent" style="display: inline"><span class="fa fa-circle-o-notch fa-spin" style="font-size:24px"></span>  Your PDF is getting ready, please wait.. </div></div>');
		$('.wikiinfo').show();
		interval=setInterval(checkPDFFile, 3000);
	});