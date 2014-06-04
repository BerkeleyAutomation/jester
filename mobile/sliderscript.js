window.onload = function()
{
	var moved = false;
	document.getElementById('ratings').onmouseover = movSlid;
}

function getMouseX (e)
{ 
  	if (!e) e = window.event;

	if (e)
	{ 
		if (e.pageX || e.pageY)
		{
			return e.pageX;
		}
		else if (e.clientX || e.clientY)
		{
			return e.clientX + document.body.scrollLeft;
		}  
	}
	
	return null;
}

function movSlid (e)
{
	var length = 638;
	var evt = e || event;
	var xcoord = getMouseX(e);
	var newxcoord;
	var oldscreenpos = evt.screenX;
	var oldscreenypos = evt.screenY;
	
	this.onmousemove = function(e)
	{
		var evt = e || event;
		var movedpixels = evt.screenX - oldscreenpos;
		var movedypixels = evt.screenY - oldscreenypos;
		newxcoord = xcoord + movedpixels - 46;
		
		if (movedpixels == 0 && movedypixels == 0 && window.moved == false)
		{
			window.moved = true;
			newxcoord = 320;
		}
			
		if (newxcoord < 1)  //This block sets the stops on the slider
			newxcoord = 1;
		else if (newxcoord > length - 1)
			newxcoord = length - 1;
	
		document.getElementById('slider').style.display = "block";
		document.getElementById('slider').style.top = 2 + "px";
		document.getElementById('slider').style.left = newxcoord + "px";
		document.getElementById('slider').style.height = 26 + "px";
	
	 	if (newxcoord == 1 || newxcoord == length - 1){
			document.getElementById('slider').style.top = 12 + "px";
			document.getElementById('slider').style.height = 6 + "px";
		} else if (newxcoord == 2 || newxcoord == length - 2){
			document.getElementById('slider').style.top = 10 + "px";
			document.getElementById('slider').style.height = 10 + "px";
		} else if (newxcoord == 3 || newxcoord == length - 3){
			document.getElementById('slider').style.top = 8 + "px";
			document.getElementById('slider').style.height = 14 + "px";
		} else if (newxcoord == 4 || newxcoord == length - 4){
			document.getElementById('slider').style.top = 6 + "px";
			document.getElementById('slider').style.height = 18 + "px";
		} else if (newxcoord == 5 || newxcoord == length - 5){
			document.getElementById('slider').style.top = 4 + "px";
			document.getElementById('slider').style.height = 22 + "px";
		} else if (newxcoord == 6 || newxcoord == 7 || newxcoord == length - 6 || newxcoord == length - 7){
			document.getElementById('slider').style.top = 3 + "px";
			document.getElementById('slider').style.height = 24 + "px";
		}
  	}

	this.onmouseout = function()
	{
		this.onmousemove = function() {;}
	}
}