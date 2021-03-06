<?php declare(strict_types=1);

namespace App\Component\FussballDe;

use App\Component\Dto\FussballDeRequest;
use App\Component\FussballDe\Model\MainInfo\GamesInterface;
use App\Component\FussballDe\Model\TableResultInterface;
use App\Component\FussballDe\Model\TeamsInfoInterface;

final class FussballDeClient implements FussballDeClientInterface
{
    public function __construct(
        private readonly TeamsInfoInterface   $teamsInfo,
        private readonly GamesInterface       $gamesCrawler,
        private readonly TableResultInterface $tableResult
    )
    {
    }

    /**
     * @param \App\Component\Dto\FussballDeRequest $fussballDeRequest
     *
     * @return \App\Component\Dto\ClubTeamInfoTransfer[]
     */
    public function teamsInfo(FussballDeRequest $fussballDeRequest): array
    {
        return $this->teamsInfo->crawler($fussballDeRequest);
    }

    /**
     * @param \App\Component\Dto\FussballDeRequest $fussballDeRequest
     *
     * @return \App\Component\Dto\ClubMatchInfoTransfer[]
     */
    public function prevClubGames(FussballDeRequest $fussballDeRequest): array
    {
        return $this->gamesCrawler->getPrevClubGames($fussballDeRequest);
    }

    /**
     * @param \App\Component\Dto\FussballDeRequest $fussballDeRequest
     *
     * @return \App\Component\Dto\ClubMatchInfoTransfer[]
     */
    public function nextClubGames(FussballDeRequest $fussballDeRequest): array
    {
        return $this->gamesCrawler->getNextClubGames($fussballDeRequest);
    }

    /**
     * @param \App\Component\Dto\FussballDeRequest $fussballDeRequest
     *
     * @return \App\Component\Dto\ClubMatchInfoTransfer[]
     */
    public function prevTeamGames(FussballDeRequest $fussballDeRequest): array
    {
        return $this->gamesCrawler->getPrevTeamGames($fussballDeRequest);
    }

    /**
     * @param \App\Component\Dto\FussballDeRequest $fussballDeRequest
     *
     * @return \App\Component\Dto\ClubMatchInfoTransfer[]
     */
    public function nextTeamGames(FussballDeRequest $fussballDeRequest): array
    {
        return $this->gamesCrawler->getNextTeamGames($fussballDeRequest);
    }

    /**
     * @param \App\Component\Dto\FussballDeRequest $fussballDeRequest
     *
     * @return \App\Component\Dto\TeamTableTransfer[]
     */
    public function teamTable(FussballDeRequest $fussballDeRequest): array
    {
        return $this->tableResult->get($fussballDeRequest);
    }
}
