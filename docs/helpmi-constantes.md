# HelpMI — constantes de `wp-config.php`

El shortcode `[helpmi]` (`wp-content/themes/basel-child/functions.php`, `helpmi_function()`) necesita estas dos constantes. `wp-config.php` no está versionado (`.gitignore`: `/wp-*.php`), así que hay que añadirlas a mano en cada entorno, antes de la línea `/* That's all, stop editing! */`.

```php
// HelpMI — portal de reservas incrustado con [helpmi]
define( 'MGMIT_HELPMI_URL',           'https://helpmi.megemit.org' ); // sin barra final
define( 'MGMIT_HELPMI_SHARED_SECRET', 'put-the-shared-secret-here' ); // secreto HMAC compartido con HelpMI
```

- `MGMIT_HELPMI_URL`: base del portal. El iframe carga `{URL}/de/buchen?user=…&ts=…&sig=…`.
- `MGMIT_HELPMI_SHARED_SECRET`: clave de la firma `HMAC-SHA256` de `user_login|timestamp`. Debe ser idéntica a la configurada en HelpMI. **No subirla al repositorio.**
- Si falta alguna de las dos, el shortcode lanza un error fatal al renderizarse la página.
