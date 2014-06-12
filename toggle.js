
function toggle(id)
{
	div=document.getElementById(id);
	style=div.style.display;
	if(style=="block")
	{
		if(div.style.setProperty)
		{
			div.style.setProperty('display','none','');
		}
		else
		{
			div.style.setAttribute('display','none');
		}
	}
	else
	{
		if(div.style.setProperty)
		{
			div.style.setProperty('display','block','');
		}
		else
		{
			div.style.setAttribute('display','block');
		}
	}
}
