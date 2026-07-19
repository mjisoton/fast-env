# FastEnv

An ultra-fast, zero-dependency compiled `.env` loader designed for high-performance PHP frameworks like FlightPHP. It compiles a standard `.env` text file down to static PHP bytecode arrays that load out of OPcache RAM instantly, bypassing disk parsing on subsequent hits.

## Installation

Add this package to your project repository via Composer:

```bash
composer require mjisoton/fast-env
