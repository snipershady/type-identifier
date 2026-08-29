<?php

declare(strict_types=1);

/*
 * Copyright (C) 2025  Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA 02110-1301 USA.
 */

namespace TypeIdentifier\Exception;

/**
 * Thrown when an array nests deeper than the allowed $maxDepth.
 *
 * This is a runtime condition driven by the *data*, not a programming error:
 * arrays reach the service straight from untrusted sources (request payloads,
 * decoded JSON, self-referencing structures), so the caller is expected to
 * catch it and reject the input.
 *
 * Without this guard an unbounded structure — most notably a self-referencing
 * array — would recurse until the process dies on a non-catchable
 * "Allowed memory size exhausted" fatal error.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class MaxDepthExceededException extends \RuntimeException implements TypeIdentifierExceptionInterface
{
    /**
     * @param int<0, max> $maxDepth the limit that was exceeded
     */
    public static function create(int $maxDepth): self
    {
        return new self(sprintf(
            'Maximum array nesting depth of %d exceeded. Raise the $maxDepth argument of getTypedValue() if the input is trusted, or reject the payload.',
            $maxDepth
        ));
    }
}
