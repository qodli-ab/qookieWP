(() => {
    let popup;
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!form.matches('[data-qq-connect], [data-qq-disconnect]')) return;
        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) { event.preventDefault(); return; }
        if (form.matches('[data-qq-connect]')) popup = window.open('', 'qookie-connect', 'width=640,height=780');
        else form.querySelector('button').disabled = true;
    });
    window.addEventListener('message', event => {
        if (event.origin === window.location.origin && event.source === popup && event.data?.type === 'qookie-connected') window.location.reload();
    });
})();
