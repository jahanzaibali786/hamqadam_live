importScripts("https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js");
importScripts("https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js");
importScripts("./firebase-messaging-config.js");

(function () {
    var config = self.HAMQADAM_FIREBASE_CONFIG || {};
    var required = ['apiKey', 'authDomain', 'projectId', 'messagingSenderId', 'appId'];
    var ready = required.every(function (key) {
        return typeof config[key] === 'string' && config[key].trim() !== '';
    });

    if (!ready) {
        return;
    }

    firebase.initializeApp(config);
    var messaging = firebase.messaging();
    messaging.setBackgroundMessageHandler(function (payload) {
        var data = payload && payload.data ? payload.data : {};
        return self.registration.showNotification(data.title || 'Hamqadam', {
            body: data.body || '',
            icon: data.icon || undefined
        });
    });
}());
