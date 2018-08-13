$("document").ready(function(){
	$menus = $(".dropdown");


	$menus.off("mouseenter").on("mouseenter", function(){
		var $this = $(this);
		$this.children("ul").show(200);
		$this.addClass("open");
		return false;
	});

	$menus.off("mouseleave").on("mouseleave", function(){
		var $this = $(this);
		$this.children("ul").hide(100);
		$this.removeClass("open");
		return false;
	});
	
	$menus.off("click").on("click", function(){
		var $this = $(this);
		$this.removeClass("open");
		// return false;
	});
	
	$menus.children("a").off("click").on("click", function(){
		var $this = $(this);
		window.open($this.attr("href"));
		return false;
	});
	
	$atimTest = $(".atim-test");
	$atimTest.off("click").on("click", function(){
		alert("This is a TEST version of ATiM and the data that you entered will be deleted. For using ATiM should click on PROD link");
	});
	
});