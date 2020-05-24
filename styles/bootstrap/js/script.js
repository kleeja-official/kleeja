function ajaxRemote (method, url, data, successCallback)
{
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
        if (this.readyState == 4 && this.status == 200) {
            successCallback(this);
        }
    };
    xhttp.open(method, url, true);
    if (method == 'POST') {
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    }
    let sendData = method == 'POST' ? data : null
    xhttp.send(sendData);
}
