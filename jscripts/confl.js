 function confl(message, url) {
     	if(confirm(message)) location.href = url;
     }

$(document).ready(function() {
	$(".menu .deleteIcon").easyconfirm({ locale: { title: 'Upozornenie', button: ['No','Yes']}});
});
