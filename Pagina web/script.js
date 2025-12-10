function runQuery() {
    const query = document.getElementById("queryInput").value;

    fetch("execute.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "query=" + encodeURIComponent(query)
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById("result").textContent = data;
    })
    .catch(error => {
        document.getElementById("result").textContent = error;
    });
}
