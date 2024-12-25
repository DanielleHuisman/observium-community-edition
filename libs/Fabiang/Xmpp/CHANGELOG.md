# CHANGELOG

## 0.7.0 (2017-05-23)

- [MAJOR]: [PR #46](https://github.com/fabiang/xmpp/pull/46) Added support for password-protected chatrooms
- [MAJOR]: [PR #44](https://github.com/fabiang/xmpp/pull/44) Added anonymous authentication method
- [MAJOR]: [PR #34](https://github.com/fabiang/xmpp/pull/34) Added support for registereing user
- [MAJOR]: [PR #34](https://github.com/fabiang/xmpp/pull/34) Added vCard support
- [MAJOR]: [PR #34](https://github.com/fabiang/xmpp/pull/34) Added support for blocking and unblocking an user
- [MAJOR]: Drop support for PHP lower than 5.6
- [MAJOR]: [PR #31](https://github.com/fabiang/xmpp/pull/31): Possibility to set context for SocketClient

## 0.6.1 (2014-11-20)

- [PATCH] [Issue #4](https://github.com/fabiang/xmpp/issues/4):  Incomplete buffer response

## 0.6.0 (2014-11-13)

- [MINOR] [Issue #3](https://github.com/fabiang/xmpp/issues/3): Library now tries to reconnect via TLS if connection with TCP failed
- [PATCH]: Reducing output for blocking listeners.

## 0.5.0 (2014-10-29)

- [MINOR]: Messages get now quoted
- [MINOR]: Classes are now autoloaded with PSR-4
- [PATCH]: Cleanups

## 0.4.0 (2014-02-28)

- [MINOR]: Added a timeout to connection

## 0.3.0 (2014-02-05)

- [MINOR]: Digest-MD5 authentication wasn't working
- [PATCH]: various code optimizations

## 0.2.0 (2014-01-27)

- [MINOR]: Added support for DIGEST-MD5 authentication
- [PATCH]: Fixed a bug in xml parser, which triggered wrong events

## 0.1.0 (2014-01-23)

- [MINOR]: First release
