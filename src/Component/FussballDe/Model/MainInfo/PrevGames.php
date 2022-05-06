<?php declare(strict_types=1);

namespace App\Component\FussballDe\Model\MainInfo;

use App\Component\Crawler\Bridge\HttpClientInterface;
use App\Component\Dto\ClubMatchInfoTransfer;
use App\Component\Dto\FussballDeRequest;
use App\Component\FussballDe\Font\DecodeProxyInterface;

final class PrevGames implements PrevGamesInterface
{
    private const URL = '/ajax.club.prev.games/-/id/%s/mode/PAGE';
    private const XPATH = '//*[contains(@class, "%s")]';

    public function __construct(
        private HttpClientInterface  $crawlerClient,
        private DecodeProxyInterface $decodeProxy,
    )
    {
    }

    /**
     * @param \App\Component\Dto\FussballDeRequest $fussballDeRequest
     *
     * @return \App\Component\Dto\ClubMatchInfoTransfer[]
     */
    public function get(FussballDeRequest $fussballDeRequest): array
    {
        $html = $this->crawlerClient->getHtml(
            $this->getUrl($fussballDeRequest)
        );

        $dom = new \DOMDocument();

        /**
         * $html is no empty, i check this in getHtml Method
         * @psalm-suppress ArgumentTypeCoercion
         */
        $dom->loadHTML(str_replace('&#', '', $html));

        $clubMatchInfoTransferList = [];

        $clubMatchInfoTransferList = $this->addDateAndCompetitionInfo($dom, $clubMatchInfoTransferList);

        $this->addScoreInfo($dom, $clubMatchInfoTransferList);


        return $clubMatchInfoTransferList;
    }

    /**
     * @param \DOMDocument $dom
     * @param array $clubMatchInfoTransferList
     *
     * @return \App\Component\Dto\ClubMatchInfoTransfer[]
     */
    private function addDateAndCompetitionInfo(\DOMDocument $dom, array $clubMatchInfoTransferList): array
    {
        $matchDateAndCompetitionInfo = $this->getNodeListByClass($dom, 'visible-small');

        /** @var \DOMElement $info */
        foreach ($matchDateAndCompetitionInfo as $key => $info) {
            $nodeValue = trim($info->nodeValue);

            $clubMatchInfoTransfer = new ClubMatchInfoTransfer();

            $dateTimeInfo = trim(strstr($nodeValue, '|', true));

            $clubMatchInfoTransfer->time = substr($dateTimeInfo, -9, 5);
            $clubMatchInfoTransfer->date = substr($dateTimeInfo, -22, 10);

            $competitionInfo = explode(
                ' | ',
                substr(strstr($nodeValue, '|'), 2)
            );

            $clubMatchInfoTransfer->ageGroup = $competitionInfo[0];
            $clubMatchInfoTransfer->competition = $competitionInfo[1];

            $clubMatchInfoTransferList[$key] = $clubMatchInfoTransfer;
        }

        return $clubMatchInfoTransferList;
    }

    /**
     * @param \DOMDocument $dom
     * @param \App\Component\Dto\ClubMatchInfoTransfer[] $clubMatchInfoTransferList
     *
     * @return \App\Component\Dto\ClubMatchInfoTransfer[]
     */
    private function addScoreInfo(\DOMDocument $dom, array $clubMatchInfoTransferList): array
    {
        $matchScore = $this->getNodeListByClass($dom, 'column-score');

        /** @var \DOMElement $info */
        foreach ($matchScore as $key => $info) {
            /** @var \DOMElement $matchInfo */
            $matchInfo = $info->previousElementSibling->getElementsByTagName('span')[0];

            $clubMatchInfoTransferList[$key]->awayTeam = utf8_decode($matchInfo->getAttribute('data-alt'));
            $clubMatchInfoTransferList[$key]->awayLogo = 'https:' . $matchInfo->getAttribute('data-responsive-image');

            $matchInfo = $info->previousElementSibling->previousElementSibling->previousElementSibling->getElementsByTagName('span')[0];

            $clubMatchInfoTransferList[$key]->homeTeam = utf8_decode($matchInfo->getAttribute('data-alt'));
            $clubMatchInfoTransferList[$key]->homeLogo = 'https:' . $matchInfo->getAttribute('data-responsive-image');

            $result = trim($info->nodeValue);

            if (str_contains($result, ':')) {
                $decodeFontName = $info->childNodes[1]->firstChild->getAttribute('data-obfuscation');
                $fontInfo = $this->decodeProxy->decodeFont($decodeFontName);

                $scoreInfo = explode(':', $result);

                $clubMatchInfoTransferList[$key]->homeScore = $this->getScore($scoreInfo[0], $fontInfo);
                $clubMatchInfoTransferList[$key]->awayScore = $this->getScore($scoreInfo[1], $fontInfo);
            }
        }

        return $clubMatchInfoTransferList;
    }

    private function getUrl(FussballDeRequest $fussballDeRequest): string
    {
        return sprintf(
            self::URL,
            $fussballDeRequest->id
        );
    }

    private function getNodeListByClass(\DOMDocument $dom, string $class, ?\DOMNode $contextNode = null): \DOMNodeList
    {
        $xpath = new \DOMXPath($dom);
        $domNodeList = $xpath->query(
            sprintf(self::XPATH, $class),
            $contextNode
        );

        if (!$domNodeList instanceof \DOMNodeList || $domNodeList->length === 0) {
            throw new \RuntimeException('Empty');
        }
        return $domNodeList;
    }

    private function getScore(string $scoreInfo, array $fontInfo): string
    {
        $scoreHome = array_filter(explode(';', $scoreInfo));

        $finalScore = '';
        foreach ($scoreHome as $score) {
            $finalScore .= $fontInfo[strtolower($score)];
        }

        return $finalScore;
    }
}
