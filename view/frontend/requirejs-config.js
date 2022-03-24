var config = {
    paths: {
        'clipboard': 'Paymee_Core/js/util/clipboard',
    },
    shim: {
        'clipboard': {
            exports: 'ClipboardJS',
            deps: ['jquery']
        },
    },
};
