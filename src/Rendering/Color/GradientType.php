<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Color;

enum GradientType
{
    case LeftToRight;
    case RightToLeft;
    case TopToBottom;
    case BottomToTop;
    case TopLeftDiagonal;
    case TopRightDiagonal;
    case BottomLeftDiagonal;
    case BottomRightDiagonal;
    case Radial;
}
