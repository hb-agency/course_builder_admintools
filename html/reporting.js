window.addEvent('domready', function() { 

	if (tl_buttons)
	{
		var btnPrint  = new Element('a', {class: 'print_report', title: 'Print a report to PDF.', href: 'contao/main.php?do=tl_cb_reporting&act=print_report'});
		tl_buttons.inject(btnPrint, 'before');
	}
	alert('test');

});
