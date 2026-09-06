document.addEventListener("DOMContentLoaded", () => {

    const inputs =
    document.querySelectorAll(".custom-input");

    inputs.forEach(input => {

        input.addEventListener("focus", () => {

            input.style.boxShadow =
            "0 0 10px rgba(37,99,235,0.5)";

        });

        input.addEventListener("blur", () => {

            input.style.boxShadow = "none";

        });

    });

});

function previewImage(input, previewId){

    const preview =
    document.getElementById(previewId);

    const file = input.files[0];

    const reader = new FileReader();

    reader.onload = function(e){

        preview.src = e.target.result;

    }

    reader.readAsDataURL(file);

}