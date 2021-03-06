<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin/ajax")
 *
 */
class AjaxController extends AbstractController
{
    /**
     * @Route("/casrec-delete-by-source/{source}", name="casrec_delete_by_source_ajax")
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_AD')")
     */
    public function deleteUsersBySourceAjaxAction(Request $request, $source)
    {
        try {
            $before = $this->getRestClient()->get('casrec/count', 'array');
            $this->getRestClient()->delete('casrec/delete-by-source/'.$source);
            $after = $this->getRestClient()->get('casrec/count', 'array');

            return new JsonResponse(['before'=>$before, 'after'=>$after]);
        } catch (\Throwable $e) {
            return new JsonResponse($e->getMessage());
        }
    }

    /**
     * @Route("/casrec-add", name="casrec_add_ajax")
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_AD')")
     */
    public function uploadUsersAjaxAction(Request $request)
    {
        $chunkId = 'chunk' . $request->get('chunk');
        /** @var \Redis $redis */
        $redis = $this->get('snc_redis.default');

        try {
            $compressedData = $redis->get($chunkId);
            if ($compressedData) {
                $ret = $this->getRestClient()->setTimeout(600)->post('v2/lay-deputyship/upload', $compressedData);
                $redis->del($chunkId); //cleanup for next execution
            } else {
                $ret['added'] = 0;
            }

            return new JsonResponse($ret);
        } catch (\Throwable $e) {
            return new JsonResponse($e->getMessage());
        }
    }
}
