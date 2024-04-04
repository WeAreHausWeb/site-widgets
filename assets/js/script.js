// Touch device
window.is_touch_device = function () {
    try {
        document.createEvent("TouchEvent");
        return true;
    } catch (e) {
        return false;
    }
}

if (is_touch_device()) {
    document.documentElement.classList.add('touch');
} else {
    document.documentElement.classList.add('no-touch');
}


//------------------------------------------------------------------------------------------//
// VIEWPORT, DEVICE ETC
//------------------------------------------------------------------------------------------//

// Fix mobile 100vh with calc() css function - https://ilxanlar.medium.com/you-shouldnt-rely-on-css-100vh-and-here-s-why-1b4721e74487#b630
window.appHeight = function() {
    const doc = document.documentElement;
    doc.style.setProperty('--vh', (window.innerHeight*.01) + 'px');
}
window.addEventListener('resize', appHeight);
// Do initial
appHeight();

