//#region assets/js/script.js
window.is_touch_device = function() {
	try {
		document.createEvent("TouchEvent");
		return true;
	} catch (e) {
		return false;
	}
};
if (is_touch_device()) document.documentElement.classList.add("touch");
else document.documentElement.classList.add("no-touch");
window.appHeight = function() {
	document.documentElement.style.setProperty("--vh", window.innerHeight * .01 + "px");
};
window.addEventListener("resize", appHeight);
appHeight();
//#endregion

//# sourceMappingURL=script.js.map