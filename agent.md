# Proyecto WordPress: Megemit

Este archivo contiene información técnica sobre el entorno de WordPress, versiones de software y plugins instalados.

## 🛠 Entorno del Sistema

| Componente | Versión | Notas |
| :--- | :--- | :--- |
| **WordPress** | 6.9.4 | Detectado en `wp-includes/version.php` |
| **PHP** | 8.3.30 | Ejecutando vía ServBay (`php-8.3/current`) |
| **MySQL** | 8.4.8 | ServBay (Community Server) |
| **Entorno** | ServBay | Hosting local en macOS |
| **Tema Activo** | Basel Child | Tema padre: Basel (Confirmado en DB) |
| **Conexión DB** | Exitosa | Conectado vía binary de ServBay |

## 🗄 Configuración de Base de Datos

Según el archivo `wp-config.php`:

- **DB Name:** `megemit_database`
- **DB User:** `root`
- **DB Host:** `localhost`
- **Prefix:** `wpgr_`

---

## 🔌 Plugins Instalados (67 totales, 59 activos)

### ✅ Activos (59 registrados en DB)
1.  **3d-flip-book**
2.  **Ultimate_VC_Addons**
3.  **basel-post-types**
4.  **borlabs-cookie**
5.  **boxzilla**
6.  **buttonizer-multifunctional-button**
7.  **cf7-conditional-fields**
8.  **classic-editor**
9.  **cmb2**
10. **contact-form-7**
11. **contact-form-7-honeypot**
12. **contact-form-7-shortcode-enabler**
13. **disable-remove-google-fonts**
14. **download-monitor**
15. **duplicate-post**
16. **duplicator-pro** (Corregido: eliminada duplicación en DB)
17. **duracelltomi-google-tag-manager**
18. **enable-media-replace**
19. **font-awesome**
20. **if-menu**
21. **js_composer** (WPBakery Page Builder)
22. **leadin**
23. **loco-translate**
24. **mailchimp-for-wp**
25. **minervakb**
26. **pdf-embedder**
27. **recent-posts-widget-extended**
28. **redirect-redirection**
29. **redux-framework**
30. **resmushit-image-optimizer**
31. **revslider** (Slider Revolution)
32. **sendcloud-shipping**
33. **simple-membership**
34. **simple-membership-after-login-redirection**
35. **simple-membership-custom-messages**
36. **simple-membership-form-shortcode**
37. **svg-support**
38. **swpm-bbpress**
39. **swpm-custom-post-type-protection-enhanced**
40. **swpm-form-builder**
41. **swpm-full-page-protection**
42. **swpm-show-member-info**
43. **tidio-live-chat**
44. **updraftplus**
45. **user-role-editor**
46. **woo-custom-add-to-cart-button**
47. **woo-update-manager**
48. **woocommerce**
49. **woocommerce-dynamic-pricing**
50. **woocommerce-gateway-stripe**
51. **woocommerce-legacy-rest-api**
52. **woocommerce-paypal-payments**
53. **woocommerce-services**
54. **wordfence**
55. **wordpress-seo**
56. **wp-fastest-cache**
57. **wp-mail-smtp**
58. **wp-migrate-db**
59. **wp-store-locator**

### ❌ Inactivos (En el servidor pero no activados)
- **advanced-database-cleaner**
- **backup**
- **bbpress**
- **edwiser-bridge**
- **edwiser-bridge-sso**
- **selective-synchronization**
- **woocommerce-payments**
- **wp-store-locator-csv**

---

> [!TIP]
> Los binarios de ServBay se encuentran en `/Applications/ServBay/package/bin/`. Se han configurado las versiones correctas en este documento.
