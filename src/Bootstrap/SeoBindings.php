<?php

declare(strict_types=1);

namespace Maatify\Seo\Bootstrap;

use Maatify\Seo\Admin\Redirect\Service\AdminRedirectCommandService;
use Maatify\Seo\Admin\Redirect\Service\AdminRedirectQueryService;
use Maatify\Seo\Admin\SeoOverride\Service\AdminSeoOverrideCommandService;
use Maatify\Seo\Admin\SeoOverride\Service\AdminSeoOverrideQueryService;
use Maatify\Seo\Admin\SlugHistory\Service\AdminSlugHistoryCommandService;
use Maatify\Seo\Admin\SlugHistory\Service\AdminSlugHistoryQueryService;
use Maatify\Seo\Shared\Contract\HostUrlGeneratorInterface;
use Maatify\Seo\Shared\Contract\RedirectRepositoryInterface;
use Maatify\Seo\Shared\Contract\SeoOverrideRepositoryInterface;
use Maatify\Seo\Shared\Contract\SlugHistoryRepositoryInterface;
use Maatify\Seo\Shared\Infrastructure\Persistence\PdoRedirectRepository;
use Maatify\Seo\Shared\Infrastructure\Persistence\PdoSeoOverrideRepository;
use Maatify\Seo\Shared\Infrastructure\Persistence\PdoSlugHistoryRepository;
use Maatify\Seo\Shared\Service\MetaGeneratorService;
use Maatify\Seo\Shared\Service\RedirectCommandService;
use Maatify\Seo\Shared\Service\RedirectManagerService;
use Maatify\Seo\Shared\Service\RedirectQueryService;
use Maatify\Seo\Shared\Service\SchemaGeneratorService;
use Maatify\Seo\Shared\Service\SeoOverrideCommandService;
use Maatify\Seo\Shared\Service\SeoOverrideQueryService;
use Maatify\Seo\Shared\Service\SitemapGeneratorService;
use Maatify\Seo\Shared\Service\SlugHistoryCommandService;
use Maatify\Seo\Shared\Service\SlugHistoryQueryService;
use Maatify\Seo\Shared\Service\SlugHistoryService;
use Maatify\Seo\Web\SeoRender\Service\SeoPageRenderService;
use Maatify\Seo\Exception\SeoConflictException;
use PDO;

/**
 * Framework-neutral SEO dependency definitions.
 *
 * Each factory receives an array-like service map represented as
 * array<class-string, object>. Hosts may adapt these definitions to any
 * container by resolving dependencies with the same class/interface-string keys.
 *
 * Required host-provided entry:
 * - PDO::class => PDO
 *
 * Optional host-provided entry:
 * - HostUrlGeneratorInterface::class => HostUrlGeneratorInterface
 *
 * @phpstan-type ServiceMap array<class-string, object>
 * @phpstan-type BindingFactory callable(ServiceMap): object
 * @phpstan-type BindingMap array<class-string, BindingFactory>
 */
final class SeoBindings
{
    /** @return BindingMap */
    public static function shared(): array
    {
        return [
            PdoSeoOverrideRepository::class => static fn (array $services): PdoSeoOverrideRepository => new PdoSeoOverrideRepository(self::get($services, PDO::class)),
            SeoOverrideRepositoryInterface::class => static fn (array $services): SeoOverrideRepositoryInterface => self::get($services, PdoSeoOverrideRepository::class),
            SeoOverrideCommandService::class => static fn (array $services): SeoOverrideCommandService => new SeoOverrideCommandService(self::get($services, SeoOverrideRepositoryInterface::class)),
            SeoOverrideQueryService::class => static fn (array $services): SeoOverrideQueryService => new SeoOverrideQueryService(self::get($services, SeoOverrideRepositoryInterface::class)),

            PdoRedirectRepository::class => static fn (array $services): PdoRedirectRepository => new PdoRedirectRepository(self::get($services, PDO::class)),
            RedirectRepositoryInterface::class => static fn (array $services): RedirectRepositoryInterface => self::get($services, PdoRedirectRepository::class),
            RedirectCommandService::class => static fn (array $services): RedirectCommandService => new RedirectCommandService(self::get($services, RedirectRepositoryInterface::class)),
            RedirectQueryService::class => static fn (array $services): RedirectQueryService => new RedirectQueryService(self::get($services, RedirectRepositoryInterface::class)),

            PdoSlugHistoryRepository::class => static fn (array $services): PdoSlugHistoryRepository => new PdoSlugHistoryRepository(self::get($services, PDO::class)),
            SlugHistoryRepositoryInterface::class => static fn (array $services): SlugHistoryRepositoryInterface => self::get($services, PdoSlugHistoryRepository::class),
            SlugHistoryCommandService::class => static fn (array $services): SlugHistoryCommandService => new SlugHistoryCommandService(self::get($services, SlugHistoryRepositoryInterface::class)),
            SlugHistoryQueryService::class => static fn (array $services): SlugHistoryQueryService => new SlugHistoryQueryService(self::get($services, SlugHistoryRepositoryInterface::class)),

            MetaGeneratorService::class => static fn (array $services): MetaGeneratorService => new MetaGeneratorService(
                self::get($services, SeoOverrideQueryService::class),
                self::getOptional($services, HostUrlGeneratorInterface::class),
            ),
            SchemaGeneratorService::class => static fn (): SchemaGeneratorService => new SchemaGeneratorService(),
            RedirectManagerService::class => static fn (array $services): RedirectManagerService => new RedirectManagerService(
                self::get($services, RedirectQueryService::class),
                self::getOptional($services, RedirectCommandService::class),
                self::getOptional($services, HostUrlGeneratorInterface::class),
            ),
            SlugHistoryService::class => static fn (array $services): SlugHistoryService => new SlugHistoryService(
                self::get($services, SlugHistoryQueryService::class),
                self::get($services, SlugHistoryCommandService::class),
                self::getOptional($services, RedirectCommandService::class),
            ),
            SitemapGeneratorService::class => static fn (): SitemapGeneratorService => new SitemapGeneratorService(),
        ];
    }

    /** @return BindingMap */
    public static function admin(): array
    {
        return [
            AdminSeoOverrideCommandService::class => static fn (array $services): AdminSeoOverrideCommandService => new AdminSeoOverrideCommandService(self::get($services, SeoOverrideCommandService::class)),
            AdminSeoOverrideQueryService::class => static fn (array $services): AdminSeoOverrideQueryService => new AdminSeoOverrideQueryService(self::get($services, SeoOverrideQueryService::class)),
            AdminRedirectCommandService::class => static fn (array $services): AdminRedirectCommandService => new AdminRedirectCommandService(self::get($services, RedirectCommandService::class)),
            AdminRedirectQueryService::class => static fn (array $services): AdminRedirectQueryService => new AdminRedirectQueryService(self::get($services, RedirectQueryService::class)),
            AdminSlugHistoryCommandService::class => static fn (array $services): AdminSlugHistoryCommandService => new AdminSlugHistoryCommandService(self::get($services, SlugHistoryCommandService::class)),
            AdminSlugHistoryQueryService::class => static fn (array $services): AdminSlugHistoryQueryService => new AdminSlugHistoryQueryService(self::get($services, SlugHistoryQueryService::class)),
        ];
    }

    /** @return BindingMap */
    public static function web(): array
    {
        return [
            SeoPageRenderService::class => static fn (array $services): SeoPageRenderService => new SeoPageRenderService(
                self::get($services, MetaGeneratorService::class),
                self::get($services, SchemaGeneratorService::class),
                self::getOptional($services, RedirectManagerService::class),
                self::getOptional($services, SitemapGeneratorService::class),
            ),
        ];
    }

    /** @return BindingMap */
    public static function all(): array
    {
        return self::shared() + self::admin() + self::web();
    }

    /**
     * @template T of object
     * @param array<mixed> $services
     * @param class-string<T> $id
     * @return T
     */
    private static function get(array $services, string $id): object
    {
        if (! isset($services[$id])) {
            throw SeoConflictException::dueToReason('Missing SEO binding dependency [' . $id . '].');
        }

        $service = $services[$id];
        if (! $service instanceof $id) {
            throw SeoConflictException::dueToReason('SEO binding dependency [' . $id . '] has an invalid instance type.');
        }

        return $service;
    }

    /**
     * @template T of object
     * @param array<mixed> $services
     * @param class-string<T> $id
     * @return T|null
     */
    private static function getOptional(array $services, string $id): ?object
    {
        if (! isset($services[$id])) {
            return null;
        }

        return self::get($services, $id);
    }
}
