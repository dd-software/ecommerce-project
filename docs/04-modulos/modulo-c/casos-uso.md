# Casos de Uso

## Proyecto

**Plataforma E-commerce con Gestión de Inventarios y Pagos**

## Módulo

**Módulo C - Autenticación**

---

# CU-C-001 Flujo Principal de Autenticación

## Objetivo

Permitir que un usuario acceda de forma segura a la plataforma mediante un proceso de autenticación.

---

## Actores

### Actor Principal

* Usuario

### Actores Secundarios

* Servicio de Autenticación
* Base de Datos de Usuarios
* Sistema de Auditoría

---

## Descripción

El usuario proporciona sus credenciales de acceso para ser validado por el sistema. Si las credenciales son correctas, se concede acceso a las funcionalidades autorizadas de la plataforma.

---

## Precondiciones

1. El usuario se encuentra registrado en la plataforma.
2. El servicio de autenticación está disponible.
3. La base de datos de usuarios está operativa.

---

## Disparador

El usuario intenta acceder a la plataforma.

---

## Flujo Principal

| Paso | Acción                                                  |
| ---- | ------------------------------------------------------- |
| 1    | El usuario accede a la pantalla de autenticación.       |
| 2    | El usuario ingresa sus credenciales.                    |
| 3    | El sistema valida el formato de los datos recibidos.    |
| 4    | El sistema consulta la información del usuario.         |
| 5    | El servicio de autenticación verifica las credenciales. |
| 6    | El sistema genera una sesión válida.                    |
| 7    | El sistema registra el evento de acceso.                |
| 8    | El usuario accede a la plataforma.                      |

---

## Flujos Alternativos

### FA-001 Credenciales Inválidas

| Paso | Acción                                                  |
| ---- | ------------------------------------------------------- |
| 1    | El usuario ingresa credenciales incorrectas.            |
| 2    | El sistema rechaza la autenticación.                    |
| 3    | Se muestra un mensaje indicando credenciales inválidas. |
| 4    | El usuario puede intentar nuevamente.                   |

---

### FA-002 Usuario Inactivo

| Paso | Acción                                                  |
| ---- | ------------------------------------------------------- |
| 1    | El sistema identifica que la cuenta está inactiva.      |
| 2    | La autenticación es rechazada.                          |
| 3    | Se informa al usuario que la cuenta no está disponible. |

---

## Flujos de Excepción

### FE-001 Servicio de Autenticación No Disponible

| Paso | Acción                                                                  |
| ---- | ----------------------------------------------------------------------- |
| 1    | El sistema intenta validar credenciales.                                |
| 2    | El servicio de autenticación no responde.                               |
| 3    | El incidente es registrado.                                             |
| 4    | Se informa al usuario que el servicio no está disponible temporalmente. |

---

### FE-002 Error de Base de Datos

| Paso | Acción                                                |
| ---- | ----------------------------------------------------- |
| 1    | El sistema intenta consultar información del usuario. |
| 2    | Ocurre un error de acceso a datos.                    |
| 3    | El incidente es registrado.                           |
| 4    | Se muestra un mensaje de error controlado.            |

---

## Postcondiciones

### Éxito

* El usuario dispone de una sesión válida.
* El acceso queda registrado para auditoría.
* Se habilitan las funcionalidades autorizadas.

### Fallo

* No se crea sesión.
* El acceso es denegado.
* El incidente queda registrado.

---

## Reglas de Negocio

### RN-001

Solo usuarios registrados pueden autenticarse.

### RN-002

Las credenciales deben validarse antes de otorgar acceso.

### RN-003

Los intentos fallidos deben registrarse para auditoría.

### RN-004

Las cuentas inactivas no pueden iniciar sesión.

### RN-005

Las comunicaciones deben realizarse mediante HTTPS.

---

## Prioridad

Alta

---

## Frecuencia de Uso

Muy Alta

---

## Requisitos Relacionados

| Código     | Descripción                        |
| ---------- | ---------------------------------- |
| RF-AUT-001 | Validar credenciales de usuario    |
| RF-AUT-002 | Crear sesión autenticada           |
| RF-AUT-003 | Registrar eventos de acceso        |
| RF-AUT-004 | Gestionar errores de autenticación |

---

## API Relacionada

### Verificación de Servicio de Autenticación

```http
GET /api/autenticacion
```

### Respuesta

```json
{
  "success": true
}
```

---

## Criterios de Aceptación

* El usuario puede autenticarse con credenciales válidas.
* Las credenciales inválidas son rechazadas.
* Los accesos son registrados correctamente.
* Los errores son manejados de forma controlada.
* El servicio responde dentro de los tiempos definidos.
* La autenticación cumple los requisitos de seguridad establecidos.

---

## Trazabilidad

| Artefacto               | Referencia              |
| ----------------------- | ----------------------- |
| API Contract            | api-contract.md         |
| Especificación          | spec.md                 |
| Testing                 | testing.md              |
| Criterios de Aceptación | criterios-aceptacion.md |
| Riesgos                 | riesgos.md              |

---

## Estado

**Versión:** 1.0

**Estado:** En Diseño

**Aprobación:** Pendiente
