<?php

namespace Tests\Feature;

use Dotenv\Dotenv;
use Tests\TestCase;

/**
 * Blueprint §51 17.19: "APP_DEBUG=false" in production is
 * non-negotiable, and CLAUDE.md's Security Hardening (17d) section
 * already explains why that can't be enforced from application code --
 * it's an environment choice a deployer makes. This test's whole job is
 * guarding the one place that choice IS committed to the repo: the
 * .env.production.example template a real deployment copies from.
 */
class ProductionEnvironmentTemplateTest extends TestCase
{
    private function parsed(): array
    {
        return Dotenv::parse(file_get_contents(base_path('.env.production.example')));
    }

    public function test_app_debug_is_false(): void
    {
        $this->assertSame('false', $this->parsed()['APP_DEBUG']);
    }

    public function test_app_env_is_production(): void
    {
        $this->assertSame('production', $this->parsed()['APP_ENV']);
    }

    public function test_session_cookies_are_forced_secure(): void
    {
        $this->assertSame('true', $this->parsed()['SESSION_SECURE_COOKIE']);
    }

    public function test_mail_is_not_left_on_the_log_driver(): void
    {
        $this->assertNotSame('log', $this->parsed()['MAIL_MAILER']);
    }

    public function test_queue_and_cache_use_redis_not_the_local_database_fallback(): void
    {
        $env = $this->parsed();
        $this->assertSame('redis', $env['QUEUE_CONNECTION']);
        $this->assertSame('redis', $env['CACHE_STORE']);
    }

    public function test_database_connection_is_mysql_not_the_local_sqlite_fallback(): void
    {
        $this->assertSame('mysql', $this->parsed()['DB_CONNECTION']);
    }
}
