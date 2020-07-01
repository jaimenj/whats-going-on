window.onload = () => {
    function submitForm() {
        document.getElementById('this_form').submit();
    }

    document.getElementById('title-last-blocks').addEventListener('click', (e) => {
        document.getElementById('block-last-blocks').classList.toggle("wgo-collapse-down");
    });

    document.getElementById('title-ips-blocked').addEventListener('click', () => {
        document.getElementById('block-ips-blocked').classList.toggle("wgo-collapse-down");
    });
}