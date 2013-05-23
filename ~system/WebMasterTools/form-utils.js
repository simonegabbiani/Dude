/* form-utils.js */
function WMT_FM_updateDate(id) {
	document.getElementById(id).value = 
		document.getElementById(id+'_yy').value + '-' +
		document.getElementById(id+'_mm').value + '-' +
		document.getElementById(id+'_dd').value;
}
function WMT_FM_updateTime(id) {
	document.getElementById(id).value = 
		document.getElementById(id+'_hh').value + ':' +
		document.getElementById(id+'_mm').value;
}