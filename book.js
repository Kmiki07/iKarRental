const form = document.querySelector('form');
console.log(form);
const formData = new FormData(form);
form.addEventListener("submit", function(e) {
    e.preventDefault();
    book();
});

function getUrlParams() {
    const params = {};
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    urlParams.forEach((value, key) => {
        params[key] = value;
    });
    return params;
}


async function book() {
    // console.log(getUrlParams());
    // car_id = $_GET['id'];
    // startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
    // endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;
    // const response = await fetch(`ajaxbook.php?id=${$car_id}&startDate=${$startDate}&endDate=${$endDate}`);
    // const responsediv = document.querySelector("#bookingresponse");
    // responsediv.innerHTML = await response.text();

    const urlParams = getUrlParams();
    const formData = new FormData(form);
    for (const [key, value] of Object.entries(urlParams)) {
        formData.append(key, value);
    }

    const response = await fetch("ajaxbook.php", {
        method: "POST",
        body: formData
    });
    console.log(response);
    const data = await response.json();
    console.log(data);
    const bookingresponse = document.querySelector("#bookingresponse");
    if (data.success) {
        bookingresponse.innerHTML = data.html;
    } else {
        bookingresponse.innerHTML = data.html;
        //console.log(data.html);
    }

}