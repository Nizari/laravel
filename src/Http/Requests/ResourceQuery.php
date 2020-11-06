<?php
/**
 * Copyright 2020 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Http\Requests;

use Illuminate\Http\Response;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Query\FieldSets;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Query\SortFields;
use LaravelJsonApi\Core\Resolver\ResourceRequest as ResourceRequestResolver;
use Symfony\Component\HttpKernel\Exception\HttpException;
use function array_key_exists;

class ResourceQuery extends FormRequest implements QueryParameters
{

    /**
     * @var callable|null
     */
    private static $queryManyResolver;

    /**
     * @var callable|null
     */
    private static $queryOneResolver;

    /**
     * @var string[]
     */
    protected array $mediaTypes = [
        self::JSON_API_MEDIA_TYPE,
    ];

    /**
     * Specify the callback to use to guess the request class for querying many resources.
     *
     * @param callable $resolver
     * @return void
     */
    public static function guessQueryManyUsing(callable $resolver): void
    {
        self::$queryManyResolver = $resolver;
    }

    /**
     * Resolve the request instance when querying many resources.
     *
     * @param string $resourceType
     * @return QueryParameters
     */
    public static function queryMany(string $resourceType): QueryParameters
    {
        $resolver = self::$queryManyResolver ?: new ResourceRequestResolver('CollectionQuery');

        return $resolver($resourceType);
    }

    /**
     * Specify the callback to use to guess the request class for querying one resource.
     *
     * @param callable $resolver
     * @return void
     */
    public static function guessQueryOneUsing(callable $resolver): void
    {
        self::$queryOneResolver = $resolver;
    }

    /**
     * Resolve the request instance when querying one resource.
     *
     * @param string $resourceType
     * @return QueryParameters
     */
    public static function queryOne(string $resourceType): QueryParameters
    {
        $resolver = self::$queryManyResolver ?: new ResourceRequestResolver('Query');

        return $resolver($resourceType);
    }

    /**
     * @return array
     */
    public function validationData()
    {
        return $this->query();
    }

    /**
     * @inheritDoc
     */
    public function includePaths(): ?IncludePaths
    {
        $data = $this->validated();

        if (array_key_exists('include', $data)) {
            return IncludePaths::fromString($data['include'] ?: '');
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function sparseFieldSets(): ?FieldSets
    {
        $data = $this->validated();

        if (array_key_exists('fields', $data)) {
            return FieldSets::fromArray($data['fields']);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function sortFields(): ?SortFields
    {
        $data = $this->validated();

        if (array_key_exists('sort', $data)) {
            return SortFields::fromString($data['sort'] ?: '');
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function page(): ?array
    {
        $data = $this->validated();

        if (array_key_exists('page', $data)) {
            return $data['page'];
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function filter(): ?array
    {
        $data = $this->validated();

        if (array_key_exists('filter', $data)) {
            return $data['filter'];
        }

        return null;
    }

    /**
     * @return void
     */
    protected function prepareForValidation()
    {
        if (!$this->isAcceptableMediaType()) {
            throw $this->notAcceptable();
        }
    }

    /**
     * @return bool
     */
    protected function isAcceptableMediaType(): bool
    {
        return $this->accepts($this->mediaTypes());
    }

    /**
     * @return string[]
     */
    protected function mediaTypes(): array
    {
        return $this->mediaTypes;
    }

    /**
     * @return HttpException
     * @todo add translation
     */
    protected function notAcceptable(): HttpException
    {
        return new HttpException(
            Response::HTTP_NOT_ACCEPTABLE,
            "The requested resource is capable of generating only content not acceptable "
            . "according to the Accept headers sent in the request."
        );
    }
}
