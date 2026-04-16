function closeNotification(e) {
  var customNotification
  if (e.classList && e.classList.contains('custom-notification')) {
    customNotification = e
  } else {
    customNotification = e.target.closest('.custom-notification')
  }

  customNotification.classList.add('custom-notification--close')
}

function removeNotification(e) {
  var customNotification = e.target

  if (customNotification.classList.contains('custom-notification--close')) {
    customNotification.parentNode.remove()
  }
}

function notify(options) {
  var typeClass = ''
  var iconSvg = ''

  if (options.type === 'error') {
    typeClass = 'custom-notification--error'
    iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`
  } else if (options.type === 'important') {
    typeClass = 'custom-notification--error'
    iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>`
  } else if (options.type === 'warning') {
    typeClass = 'custom-notification--warning'
    iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`
  } else {
    iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
  }

  var html = `
    <div onanimationend="removeNotification(event)" class="custom-notification ${typeClass}">
      <span class="custom-notification__icon">
        ${iconSvg}
      </span>
      <div class="custom-notification__content">
        ${options.heading ? '<p class="custom-notification__heading">' + options.heading + '</p>' : ''}
        ${options.text ? '<p class="custom-notification__text">' + options.text + '</p>' : ''}
      </div>
      <button onclick="closeNotification(event)" class="custom-notification__close-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 24 24"
          stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  `
  var div = document.createElement('div')
  div.classList.add('custom-notification-container')
  div.innerHTML = html

  document.body.appendChild(div)

  if (options.autoCloseAfter) {
    setTimeout(function () {
      closeNotification(div.firstElementChild)
    }, options.autoCloseAfter);
  }
}