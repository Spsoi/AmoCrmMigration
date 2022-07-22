

let ConstructorFetch = async function (method = 'GET', url = '', data = {}, mode = 'cors',) {

    let fetchBody = {
        method: method, // *GET, POST, PUT, DELETE, etc.
        mode: mode, // no-cors, *cors, same-origin
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        // credentials: 'same-origin', // include, *same-origin, omit
        credentials: 'same-origin', // include, *same-origin, omit
        // headers: {
        //     // 'Content-Type': 'application/json',
        //     'Content-Type': 'application/json;charset=utf-8',
        // //     "Access-Control-Allow-Origin": "*",
        // //     // "Access-Control-Allow-Headers": "Content-Type, Authorization, X-Requested-With"
        // },
        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *client
    }

    if (method == 'POST') {
        fetchBody.body = JSON.stringify(data)
        console.log(fetchBody);
    }
    const response = await fetch(url, fetchBody)
    .then((response) => {
        console.log(response);
        console.log(response.status);
        if (response.status === 200) {
            // ConstructorFetch('GET', 'https://host.com');
        }
    })
}
// ConstructorFetch('GET', 'https://host.com');