// public/js/script.js
let isBlue = true;

document.getElementById("colorButton").addEventListener("click", function () {
    if (isBlue) {
        document.body.style.backgroundColor = "blue";
    } else {
        document.body.style.backgroundColor = "red";
    }
    isBlue = !isBlue;
});