ready(function () {
    window.onpageshow = function () {
        var header = document.getElementById("header");
        header.style.backgroundImage = header.getAttribute("data-img");
    }
});
