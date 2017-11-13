<?php

/*
 * This file is part of the firebase-php package.
 *
 * (c) Jérôme Gamez <jerome@kreait.com>
 * (c) kreait GmbH <info@kreait.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Kreait\Firebase;

/**
 * @link https://www.firebase.com/docs/rest/guide/retrieving-data.html#section-rest-queries Querying data.
 */
class Query
{

    const LIMIT_TO_FIRST = 'limitToFirst';
    const LIMIT_TO_LAST = 'limitToLast';

    /**
     * A key to order by.
     *
     * @var string
     */
    private $orderBy;

    /**
     * A limitation.
     *
     * @var array
     */
    private $limitTo;

    /**
     * The starting point for the query.
     *
     * @var int|string
     */
    private $startAt;

    /**
     * The end point for the query.
     *
     * @var int|string
     */
    private $endAt;

    /**
     * @var bool|int|string
     */
    private $equalTo;

    /**
     * Whether the query is shallow or not.
     *
     * @var bool
     */
    private $shallow;

	/**
	 * The print mode. Can be blank for none, pretty or silent.
	 */
	private $silent;
	private $pretty;

	/**
	 * Query constructor.
	 * @param bool $shallow
	 * @param bool $silent
	 * @param bool $pretty
	 */
	public function __construct($shallow=false, $silent = false, $pretty = false)
	{
		$this->shallow = $shallow;
		$this->silent = $silent;
		$this->pretty = $pretty;

	}


	/**
     * Order results by the given child key.
     *
     * @param string $childKey The key to order by.
     *
     * @return $this
     */
    public function orderByChildKey($childKey)
    {
        $this->orderBy = $childKey;

        return $this;
    }

    /**
     * @return $this
     */
    public function orderByKey()
    {
        $this->orderBy = '$key';

        return $this;
    }

    /**
     * Order results by priority.
     *
     * @return $this
     */
    public function orderByPriority()
    {
        $this->orderBy = '$priority';

        return $this;
    }

    /**
     * Limit the result to the first x items.
     *
     * @param int $limit The number.
     *
     * @return $this
     */
    public function limitToFirst($limit)
    {
        $this->limitTo = [self::LIMIT_TO_FIRST, $limit];

        return $this;
    }

    /**
     * Limit the result to the last x items.
     *
     * @param int $limit The number.
     *
     * @return $this
     */
    public function limitToLast($limit)
    {
        $this->limitTo = [self::LIMIT_TO_LAST, $limit];

        return $this;
    }

    /**
     * Set starting point for the Query.
     *
     * @param int|string $startAt
     *
     * @return $this
     */
    public function startAt($startAt)
    {
        $this->startAt = $startAt;

        return $this;
    }

    /**
     * Set end point for the Query.
     *
     * @param int|string $endAt
     *
     * @return $this
     */
    public function endAt($endAt)
    {
        $this->endAt = $endAt;

        return $this;
    }

    /**
     * Mark query as shallow.
     *
     * @param bool $shallow
     *
     * @return $this
     */
    public function shallow($shallow)
    {
        $this->shallow = $shallow;

        return $this;
    }

	/**
	 * Mark query as silent.
	 *
	 * @param bool $silent
	 *
	 * @return $this
	 */
	public function silent($silent)
	{
		$this->silent = $silent;

		return $this;
	}

	/**
	 * Mark query as pretty print.
	 *
	 * @param bool $pretty
	 *
	 * @return $this
	 */
	public function pretty($pretty)
	{
		$this->pretty = $pretty;

		return $this;
	}


	/**
     * Return items equal to the specified key or value, depending on the order-by method chosen.
     *
     * @param int|string|bool $equalTo
     *
     * @return $this
     */
    public function equalTo($equalTo)
    {
        $this->equalTo = $equalTo;

        return $this;
    }

    /**
     * Returns an array representation of this query.
     *
     * @return array
     */
    public function toArray()
    {
        if ($this->shallow) {
        	$result = ['shallow' => 'true'];
        	if ($this->pretty) $result['print'] = 'pretty';
            return $result;
        }

        $result = [];

	    if ($this->silent)
	    {
		    $result['print'] = 'silent';
		    return $result;
	    }

	    if ($this->pretty) $result['print'] = 'pretty';

	    // An orderBy must be set for the other parameters to work
        $result['orderBy'] = json_encode($this->orderBy ?: '$key');


	    if ($this->limitTo) {
            $result[$this->limitTo[0]] = json_encode($this->limitTo[1]);
        }

        if ($this->startAt !== null) {
            $result['startAt'] = json_encode($this->startAt);
        }

        if ($this->endAt !== null) {
            $result['endAt'] = json_encode($this->endAt);
        }

        if ($this->equalTo !== null) {
            $result['equalTo'] = json_encode($this->equalTo);
        }

        return $result;
    }

    public function __toString()
    {
        return http_build_query($this->toArray(), null, '&', PHP_QUERY_RFC3986);
    }
}
